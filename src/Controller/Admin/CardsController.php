<?php
namespace App\Controller\Admin;

use App\Entity\MembershipCards;
use App\Entity\User;
use App\Service\PdfGeneratorService;
use App\Service\EmailService;
use App\Service\CardPaymentService;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
class CardsController extends AbstractController
{
    private ?string $lastGeneratedPassword = null;
    #[Route('/admin/cards', name: 'admin_cards_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q       = trim((string) $request->query->get('q', ''));
        $status  = $request->query->get('status'); // null, '1', '0'
        $page    = max(1, (int) $request->query->get('page', 1));
        $size    = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset  = ($page - 1) * $size;

        $qb = $em->getRepository(MembershipCards::class)->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u');

        $conditions = [];
        if ($q !== '') {
            $conditions[] = '(LOWER(u.firstname) LIKE :q OR LOWER(u.lastname) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(c.cardnumberC) LIKE :q)';
        }
        if ($status !== null && $status !== '') {
            $conditions[] = 'c.status = :status';
        }
        if ($conditions) { $qb->where(implode(' AND ', $conditions)); }
        if ($q !== '') { $qb->setParameter('q', '%'.strtolower($q).'%'); }
        if ($status !== null && $status !== '') { $qb->setParameter('status', (bool) ((int) $status)); }

        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(c.id)')->getQuery()->getSingleScalarResult() ?? 0);

        $cards = $qb->select('c, u')
            ->orderBy('c.issuedate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()->getResult();

        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/cards/index.html.twig', [
            'cards' => $cards,
            'q' => $q,
            'status' => $status,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/cards/new', name: 'admin_cards_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, PdfGeneratorService $pdf, UserPasswordHasherInterface $passwordHasher, EmailService $emailService, CardPaymentService $paymentService, PhotoUploadService $photoUploadService): Response
    {
        if ($request->isMethod('POST')) {
            $mode = $request->request->get('mode', 'existing');
            
            if ($mode === 'new') {
                // Créer un nouvel utilisateur
                $user = $this->createNewUser($request, $em, $passwordHasher, $photoUploadService);
                if (!$user) {
                    return $this->redirectToRoute('admin_cards_new');
                }
            } else {
                // Utiliser un utilisateur existant
                $userId = (int) $request->request->get('user_id');
                $user = $em->getRepository(User::class)->find($userId);
                
                if (!$user) {
                    $this->addFlash('error', 'Utilisateur introuvable.');
                    return $this->redirectToRoute('admin_cards_new');
                }

                // Vérifier si l'utilisateur a déjà une carte active
                $existingCard = $em->getRepository(MembershipCards::class)->findOneBy(['user' => $user, 'status' => true]);
                if ($existingCard) {
                    $this->addFlash('error', 'Cet utilisateur possède déjà une carte active.');
                    return $this->redirectToRoute('admin_cards_new');
                }

                // Traiter l'upload de photo pour utilisateur existant
                $photoFile = $request->files->get('photo_existing');
                if ($photoFile && $photoFile->isValid()) {
                    $uploadResult = $photoUploadService->uploadUserPhoto($photoFile, $user->getId());
                    if ($uploadResult['success']) {
                        $user->setPhoto($uploadResult['publicPath']);
                        $em->flush();
                        $this->addFlash('success', 'Photo mise à jour avec succès.');
                    } else {
                        $this->addFlash('warning', 'Erreur lors de la sauvegarde de la photo: ' . $uploadResult['error']);
                    }
                }
            }

            // Créer la carte (initialement inactive en attente de paiement)
            $card = new MembershipCards();
            $card->setUser($user);
            $card->setCardnumberC(sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId()));
            $card->setIssuedate(new \DateTime());
            $card->setExpiryDate(new \DateTime('+1 year'));
            $card->setStatus(false); // Inactive jusqu'au paiement
            $card->setRoleoncard($request->request->get('role') ?: 'MEMBRE');

            $em->persist($card);
            $em->flush();

            // Générer le PDF (mais la carte reste inactive)
            $this->generateCardPdf($card, $user, $pdf);
            $em->flush();

            // Si c'est un nouvel utilisateur, envoyer l'email de bienvenue
            if ($mode === 'new') {
                $tempPassword = $this->lastGeneratedPassword; // Récupérer le mot de passe généré
                $emailService->sendWelcomeEmail(
                    $user->getEmail(),
                    $user->getFirstname(),
                    $user->getLastname(),
                    $tempPassword
                );
            }

            // Créer la demande de paiement
            $paymentResult = $paymentService->createPaymentRequest($user, $card);
            
            if ($paymentResult['success']) {
                $message = $mode === 'new' 
                    ? 'Utilisateur créé et demande de paiement envoyée par email.' 
                    : 'Carte créée et demande de paiement envoyée par email.';
                $this->addFlash('success', $message);
                $this->addFlash('info', 'La carte sera activée après confirmation du paiement.');
            } else {
                $this->addFlash('error', 'Erreur lors de la création de la demande de paiement: ' . $paymentResult['error']);
            }

            return $this->redirectToRoute('admin_cards_show', ['id' => $card->getId()]);
        }

        // GET: Afficher le formulaire
        $users = $em->getRepository(User::class)->findAll();
        return $this->render('admin/cards/new.html.twig', [
            'users' => $users,
        ]);
    }

    private function createNewUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, PhotoUploadService $photoUploadService): ?User
    {
        $email = trim((string) $request->request->get('email'));
        $firstname = trim((string) $request->request->get('firstname'));
        $lastname = trim((string) $request->request->get('lastname'));

        // Validation
        if (empty($email) || empty($firstname) || empty($lastname)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return null;
        }

        // Vérifier si l'email existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
            return null;
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setPhone($request->request->get('phone', ''));
        $user->setCountry($request->request->get('country', ''));
        $user->setCity($request->request->get('city', ''));
        $user->setRoles(['ROLE_USER']);

        // Générer un mot de passe temporaire
        $tempPassword = $this->generateTempPassword();
        $this->lastGeneratedPassword = $tempPassword; // Stocker pour usage ultérieur
        $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);

        // TODO: Ajouter un système de changement de mot de passe obligatoire

        $em->persist($user);
        $em->flush(); // Sauvegarder temporairement pour obtenir l'ID

        // Traiter l'upload de la photo si elle existe
        $photoFile = $request->files->get('photo');
        if ($photoFile && $photoFile->isValid()) {
            $uploadResult = $photoUploadService->uploadUserPhoto($photoFile, $user->getId());
            if ($uploadResult['success']) {
                $user->setPhoto($uploadResult['publicPath']);
                $em->flush(); // Sauvegarder définitivement avec la photo
            } else {
                $this->addFlash('warning', 'Photo uploadée avec succès mais non sauvegardée: ' . $uploadResult['error']);
            }
        }

        // TODO: Envoyer un email de bienvenue avec le mot de passe temporaire
        $this->addFlash('info', "Mot de passe temporaire généré : {$tempPassword}");

        return $user;
    }

    private function generateTempPassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function generateCardPdf(MembershipCards $card, User $user, PdfGeneratorService $pdf): void
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $cardsDir = $projectDir . '/public/media/cards';
        if (!is_dir($cardsDir)) {
            @mkdir($cardsDir, 0775, true);
        }
        
        $filename = sprintf('card_%d_%s.pdf', $user->getId(), date('YmdHis'));
        $outputPath = $cardsDir . '/' . $filename;
        $cardPdfUrl = '/media/cards/' . $filename;

        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $pdf->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => $user->getPhoto(),
                'name' => $name,
                'phone' => $user->getPhone() ?? '',
                'nationality' => $user->getCountry() ?? '',
                'roleBadge' => $card->getRoleoncard(),
                'roleTitle' => strtoupper($card->getRoleoncard()) . "\nBINAJIA",
                'memberId' => $card->getCardnumberC(),
                'expiry' => $card->getExpiryDate()->format('d/m/Y'),
                'joinDate' => $card->getIssuedate()->format('d/m/Y'),
            ],
            $outputPath,
            'A6',
            'landscape'
        );

        $card->setPdfurl($cardPdfUrl);
    }

    #[Route('/admin/cards/{id}', name: 'admin_cards_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $card = $em->getRepository(MembershipCards::class)->find($id);
        if (!$card) { throw $this->createNotFoundException('Carte introuvable'); }

        return $this->render('admin/cards/show.html.twig', [
            'card' => $card,
        ]);
    }

    #[Route('/admin/cards/{id}/regenerate', name: 'admin_cards_regenerate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function regenerate(int $id, EntityManagerInterface $em, PdfGeneratorService $pdf, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        $card = $em->getRepository(MembershipCards::class)->find($id);
        if (!$card) { throw $this->createNotFoundException('Carte introuvable'); }
        if (!$this->isCsrfTokenValid('regenerate_card_'.$card->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        $user = $card->getUser();
        if (!$user) { throw $this->createNotFoundException('Utilisateur introuvable pour cette carte'); }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $cardsDir = $projectDir . '/public/media/cards';
        if (!is_dir($cardsDir)) {@mkdir($cardsDir, 0775, true);}        
        $stamp = date('YmdHis');
        $filename = sprintf('card_%d_%s.pdf', (int) $user->getId(), $stamp);
        $outputPath = $cardsDir . '/' . $filename;
        $cardPdfUrl = '/media/cards/' . $filename;

        // Build data for template
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');
        $roleBadge = $user->getRoleoncard() ?? 'MEMBRE';
        $roleTitle = $user->getRoleoncard() ? strtoupper($user->getRoleoncard()) . "\nBINAJIA" : 'MEMBER\nBINAJIA';
        $memberId = $card->getCardnumberC() ?? sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId());
        $expiry = $card->getExpiryDate() ? $card->getExpiryDate()->format('d/m/Y') : (new \DateTime('+1 year'))->format('d/m/Y');
        $joinDate = $card->getIssuedate() ? $card->getIssuedate()->format('d/m/Y') : (new \DateTime())->format('d/m/Y');
        $avatar = $card->getPhoto();

        $pdf->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => $avatar,
                'name' => $name,
                'phone' => $phone,
                'nationality' => $nationality,
                'roleBadge' => $roleBadge,
                'roleTitle' => $roleTitle,
                'memberId' => $memberId,
                'expiry' => $expiry,
                'joinDate' => $joinDate,
            ],
            $outputPath,
            'A6',
            'landscape'
        );

        $card->setPdfurl($cardPdfUrl);
        $em->flush();

        $this->addFlash('success', 'PDF de la carte régénéré.');
        return $this->redirectToRoute('admin_cards_show', ['id' => $card->getId()]);
    }

    #[Route('/admin/cards/{id}/revoke', name: 'admin_cards_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        $card = $em->getRepository(MembershipCards::class)->find($id);
        if (!$card) { throw $this->createNotFoundException('Carte introuvable'); }
        if (!$this->isCsrfTokenValid('revoke_card_'.$card->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        $card->setStatus(false);
        $em->flush();

        $this->addFlash('success', 'Carte révoquée.');
        return $this->redirectToRoute('admin_cards_show', ['id' => $card->getId()]);
    }
}
