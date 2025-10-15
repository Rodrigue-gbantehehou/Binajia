<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Payments;
use App\Entity\MembershipCards;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\PdfGeneratorService;
use App\Service\MailerService;
use App\Service\FileUploader;
use App\Service\MembershipCardService;

class RegistrationController extends AbstractController
{
    #[Route('/devenir-membre/submit', name: 'app_membership_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        HttpClientInterface $httpClient,
        Environment $twig,
        Security $security,
        LoggerInterface $logger,
        PdfGeneratorService $pdfGenerator,
        MailerService $mailerService,
        FileUploader $fileUploader,
        MembershipCardService $membershipCardService
    ): Response {
        $firstName = trim((string)$request->request->get('firstName'));
        $lastName  = trim((string)$request->request->get('lastName'));
        $email     = trim((string)$request->request->get('email'));
        $phone     = trim((string)$request->request->get('phone'));
        $country   = trim((string)$request->request->get('country')) ?: null;
        $birthDate = trim((string)$request->request->get('birthDate')) ?: null;
        $plan      = trim((string)$request->request->get('plan')) ?: 'standard';
        $photoData = (string)$request->request->get('photoData'); // base64 data URL (required)
        //verifier si l'utilisateur existe et qu'il à deja une carte
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $email]);
        if ($user) {
            $cardRepo = $em->getRepository(MembershipCards::class);
            $existingCard = $cardRepo->findOneBy(['user' => $user]);
            if ($existingCard) {
                return $this->json(['ok' => false, 'message' => 'Vous avez deja une carte']);
                
            }
        }
        
        // Normalize base64 data URL if sent via x-www-form-urlencoded ("+" may become spaces)
        if ($photoData !== '' && str_starts_with($photoData, 'data:image')) {
            $parts = explode(',', $photoData, 2);
            if (count($parts) === 2) {
                // Replace spaces with "+" in the base64 segment only
                $parts[1] = str_replace(' ', '+', $parts[1]);
                // Decode any percent-encoding that might be present
                $parts[1] = preg_match('/%[0-9A-Fa-f]{2}/', $parts[1]) ? rawurldecode($parts[1]) : $parts[1];
                $photoData = $parts[0] . ',' . $parts[1];
            }
        }

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
            return $this->json(['ok' => false, 'message' => 'Champs requis manquants'], 400);
        }

        // Explicit check for missing photo
        if ($photoData === '' || !str_starts_with($photoData, 'data:image')) {
            return $this->json(['ok' => false, 'message' => 'La photo est obligatoire et doit être un Data URL base64 commençant par data:image/...'], 400);
        }

        // Basic server-side phone normalization (towards E.164)
        $rawPhone = preg_replace('/\s+/', '', $phone);
        $rawPhone = preg_replace('/[^\d\+]/', '', $rawPhone);
        if ($rawPhone && $rawPhone[0] !== '+') {
            // if country code missing, reject (frontend should prefix). Safer than guessing.
            return $this->json(['ok' => false, 'message' => 'Veuillez inclure l\'indicatif pays (ex: +22912345678).'], 400);
        }
        // minimal length check (country code + local), usually >= 8
        if (strlen(preg_replace('/\D/', '', $rawPhone)) < 8) {
            return $this->json(['ok' => false, 'message' => 'Numéro de téléphone invalide.'], 400);
        }

        // Normalize country code to localized name if symfony/intl is available
        if ($country && strlen($country) === 2 && class_exists(\Symfony\Component\Intl\Countries::class)) {
            $name = \Symfony\Component\Intl\Countries::getName(strtoupper($country), 'fr');
            if ($name) {
                $country = $name;
            }
        }

        // Prevent duplicate email: reuse existing user if present
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $email]);
        $isNewUser = false;
        $plainPassword = null;
        if (!$user) {
            // Create user
            $user = new User();
            $user->setEmail($email);
            $isNewUser = true;
        }
        // Update/assign common fields
        $user->setFirstname($firstName);
        $user->setLastname($lastName);
        $user->setPhone($rawPhone);
        $user->setCountry($country);
        if (!$user->getCreatedAt()) {
            $user->setCreatedAt(new \DateTime());
        }

        if ($isNewUser) {
            // Generate a random password and hash it
            $plainPassword = bin2hex(random_bytes(6));
            $hashed = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
            $em->persist($user);
        }

        

        // Build a member ID like BjNg-YYYY-000{id}
        $memberId = sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId());

        // Photo is required: handled by FileUploader service (crop 3:4, resize 300x400)
        try {
            $avatarPath = $fileUploader->saveAvatarFromDataUrl($photoData, (int)$user->getId());
        } catch (\RuntimeException $e) {
            // In dev, expose detailed reason for faster debugging
            $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod';
            $msg = $env === 'dev' ? ('Erreur image: ' . $e->getMessage()) : 'La photo est obligatoire pour générer la carte.';
            return $this->json(['ok' => false, 'message' => $msg], 400);
        }
        $user->setPhoto($avatarPath);   
        $em->flush(); // to get an ID or save updates
        // Redirect URL to view the card (include query for plan and avatar)
        $url = $this->generateUrl('app_membership_card_generated', [
            'id' => $user->getId(),
            'plan' => $plan,
            'avatar' => $avatarPath,
        ]);

        // If payment details were provided by the frontend, store them
        $txId = $request->request->get('transactionId');
        $txStatus = $request->request->get('transactionStatus');
        $amount = $request->request->get('amount');
        $cardGenerated = false;
        if ($txId) {
            $payment = new Payments();
            if ($amount !== null && $amount !== '') {
                // store as decimal string (entity expects string)
                $payment->setAmount((string) number_format((float) $amount, 2, '.', ''));
            } else {
                $payment->setAmount('0.00');
            }
            $payment->setPaymentMethod('FedaPay');
            $payment->setPaymentdate(new \DateTime());
            $payment->setStatus($txStatus ?: 'pending');
            $payment->setReference((string) $txId);
            $payment->setUser($user);
            $em->persist($payment);
            $em->flush();

            // Server-side verification with FedaPay API if secret key available (non-fatal)
            $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? $_SERVER['FEDAPAY_SECRET_KEY'] ?? null;
            $verifiedStatus = null;
            if ($secret) {
                try {
                    $apiUrl = sprintf('https://api.fedapay.com/v1/transactions/%s', urlencode((string)$txId));
                    $resp = $httpClient->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $secret,
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 15,
                    ]);
                    if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                        $json = $resp->toArray(false);
                        $tx = $json['transaction'] ?? null;
                        if ($tx && isset($tx['status'])) {
                            $verifiedStatus = (string)$tx['status'];
                            $payment->setStatus($verifiedStatus);
                            if (isset($tx['amount'])) {
                                $payment->setAmount((string) number_format((float)$tx['amount'], 2, '.', ''));
                            }
                            $em->flush();
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore verification failures
                }
            }

            // If payment approved, send credentials + card link by email
            $finalStatus = $verifiedStatus ?: $txStatus;
            if (is_string($finalStatus) && in_array(strtolower($finalStatus), ['approved', 'succeeded', 'success', 'paid'])) {
                try {
                    // Générer la carte et le reçu + persister en base via le service
                    $result = $membershipCardService->generateAndPersist($user, $payment, $avatarPath, $memberId);
                    $cardGenerated = true;
                    $cardPdfUrl = $result['cardPdfUrl'];
                    $receiptPdfPath = $result['receiptPdfPath'];

                    // Envoi de l'e-mail avec pièce jointe
                    $loginUrl = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $cardUrlAbs = $this->generateUrl('app_membership_card_generated', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $amountTxt = $payment->getAmount();

                    $htmlContent = $twig->render('emails/membership.html.twig', [
                        'user' => $user,
                        'loginUrl' => $loginUrl,
                        'cardUrlAbs' => $cardUrlAbs,
                        'txId' => $txId,
                        'amountTxt' => $amountTxt,
                        'plainPassword' => $plainPassword,
                        'isNewUser' => $isNewUser,
                    ]);

                    $mailerService->sendMembershipEmail(
                        $user->getEmail(),
                        'Votre adhésion Binajia — Identifiants et carte membre',
                        $htmlContent,
                        $cardPdfUrl,
                        $receiptPdfPath,
                        'recu_binajia.pdf'
                    );

                    $security->login($user);
                } catch (\Throwable $e) {
                    // Log errors for diagnostics without breaking the flow
                    $logger->error('Membership email or PDF generation failed after approved payment', [
                        'userId' => $user->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // If no payment or not approved yet, still generate the card PDF and persist entity (without receipt)
        if (!$cardGenerated) {
            try {
                $membershipCardService->generateAndPersist($user, null, $avatarPath, $memberId);
            } catch (\Throwable $e) {
                // Log errors but do not block the flow
                $logger->error('Membership card PDF generation failed (pre-redirect)', [
                    'userId' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->json([
            'ok' => true,
            'cardUrl' => $url,
            'avatar' => $avatarPath,
            'redirect' => $this->generateUrl('app_user_dashboard'),
        ]);
    }

    #[Route('/membership/card/generated/{id}', name: 'app_membership_card_generated', methods: ['GET'])]
    public function cardGenerated(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }
        $memberId = sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId());

        $memberCard = $em->getRepository(MembershipCards::class)->findOneBy(['user' => $user]);
        if (!$memberCard) {
            throw $this->createNotFoundException('Carte membre introuvable');
        }
        // Optionally pass avatar path via query if saved
        $avatar = $request->query->get('avatar');
        $plan = $request->query->get('plan', 'standard');

        // Display country as localized name if possible
        $displayCountry = $user->getCountry();
        if ($displayCountry && strlen($displayCountry) === 2 && class_exists(\Symfony\Component\Intl\Countries::class)) {
            $name = \Symfony\Component\Intl\Countries::getName(strtoupper($displayCountry), 'fr');
            if ($name) {
                $displayCountry = $name;
            }
        }

        return $this->render('membership/card_generated.html.twig', [
            'user' => $user,
            'memberId' => $memberId,
            'plan' => $plan,
            'avatar' => $avatar,
            'displayCountry' => $displayCountry,
            'memberCard' => $memberCard
        ]);
    }
}
