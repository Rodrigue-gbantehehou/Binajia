<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Projets;
use App\Entity\Evenement;
use App\Entity\Partenaire;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Service\EmailService;
use App\Entity\CulturalContent;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PageController extends AbstractController
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
    }

    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    
    #[Route('/lieux', name: 'app_places')]
    public function places(EntityManagerInterface $entityManager): Response
    {
        $places = $entityManager->getRepository(CulturalContent::class)->findAll();

        return $this->render('pages/places.html.twig', [
            'places' => $places,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }

    #[Route('/devenir-membre', name: 'app_membership')]
    public function membership(): Response
    {
        // Build countries list using Symfony Intl if available
        $countries = [];
        if (class_exists(\Symfony\Component\Intl\Countries::class)) {
            $codes = \Symfony\Component\Intl\Countries::getCountryCodes();
            foreach ($codes as $code) {
                $countries[] = [
                    'code' => $code,
                    'name' => \Symfony\Component\Intl\Countries::getName($code, 'fr') ?? $code,
                ];
            }
            usort($countries, fn($a, $b) => strcmp($a['name'], $b['name']));
        } else {
            // Fallback minimal list; recommend installing symfony/intl for full list
            $countries = [
                ['code' => 'BJ', 'name' => 'Bénin'],
                ['code' => 'NG', 'name' => 'Nigéria'],
                ['code' => 'FR', 'name' => 'France'],
                ['code' => 'US', 'name' => 'États-Unis'],
            ];
        }

        // Get FedaPay public key from environment
        $fedapayPublicKey = $_ENV['FEDAPAY_PUBLIC_KEY'] ?? $_SERVER['FEDAPAY_PUBLIC_KEY'] ?? null;

        return $this->render('pages/membership.html.twig', [
            'countries' => $countries,
            'fedapay_public_key' => $fedapayPublicKey,
        ]);
    }

    // src/Controller/MembershipController.php

    #[Route('/check-email', name: 'app_check_email', methods: ['POST'])]
    public function checkEmail(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $email = trim($request->request->get('email', ''));
        if (!$email) {
            return new JsonResponse(['ok' => false, 'message' => 'Email manquant'], 400);
        }

        $exists = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($exists) {
            return new JsonResponse(['ok' => false, 'message' => 'Cet email est déjà utilisé.']);
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/avantages', name: 'app_avantage')]
    public function avantage(Request $request, EntityManagerInterface $em, PdfGeneratorService $pdfGeneratorService, EmailService $emailService): Response
    {
        // Récupérer les lieux touristiques depuis la base de données
        $places = $em->getRepository(CulturalContent::class)->findBy([], ['createdAt' => 'DESC'], 6);

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {


            $em->persist($reservation);
            //generer le pdf du facture proforma
            $timestamp = (new \DateTime())->format('YmdHis');
            $filename = 'facture_proforma_' . ($reservation->getId() ?: $timestamp) . '.pdf';
          //empecher de generer la carte si la soumission est pas valid ou a ete deja soumise
          if ($reservation->getFacturepdf() !== null) {
            $this->addFlash('warning', 'Facture proforma deja generée.');
            return $this->redirectToRoute('app_avantage');
          }
            $pdfPath = $pdfGeneratorService->generatePdf(
                'reservation/facture_proforma.html.twig',
                ['reservation' => $reservation],
                $filename,
                'A3',
                'landscape'
            );

            // Extract relative path for database storage (from /pdf/ onwards)
            $relativePath = strstr($pdfPath, '/pdf/');
            if ($relativePath === false) {
                $relativePath = '/pdf/' . $filename;
            }

            $reservation->setFacturepdf($relativePath);
           

            $em->flush();

            // Envoyer le PDF par email
            $emailService->sendReservationConfirmation($reservation, $pdfPath);

            $this->addFlash('success', 'Facture proforma générée et envoyée par email avec succès.');

        $this->redirectToRoute('app_home');
        }
        return $this->render('pages/avantage.html.twig', [
            'form' => $form->createView(),
            'places' => $places,
        ]);
    }


    // Social Impact page
    #[Route('/impact-social', name: 'app_social_impact')]
    public function socialImpact(): Response
    {
        return $this->render('pages/social_impact.html.twig');
    }

    // routes explorer
    #[Route('/explorer', name: 'app_explorer')]
    public function explorer(EntityManagerInterface $em, Request $request, PdfGeneratorService $pdfGeneratorService, EmailService $emailService): Response
    {
        // Récupérer les lieux touristiques depuis la base de données
        $places = $em->getRepository(CulturalContent::class)->findBy([], ['createdAt' => 'DESC'], 6);

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {


            $em->persist($reservation);
            //generer le pdf du facture proforma
            $timestamp = (new \DateTime())->format('YmdHis');
            $filename = 'facture_proforma_' . ($reservation->getId() ?: $timestamp) . '.pdf';
          //empecher de generer la carte si la soumission est pas valid ou a ete deja soumise
          if ($reservation->getFacturepdf() !== null) {
            $this->addFlash('warning', 'Facture proforma deja generée.');
            return $this->redirectToRoute('app_avantage');
          }
            $pdfPath = $pdfGeneratorService->generatePdf(
                'reservation/facture_proforma.html.twig',
                ['reservation' => $reservation],
                $filename,
                'A3',
                'landscape'
            );
            // Extract relative path for database storage (from /pdf/ onwards)
            $relativePath = strstr($pdfPath, '/pdf/');
            if ($relativePath === false) {
                $relativePath = '/pdf/' . $filename;
            }

            $reservation->setFacturepdf($relativePath);
            $em->flush();
            // Envoyer le PDF par email
            $emailService->sendReservationConfirmation($reservation, $pdfPath);

            $this->addFlash('success', 'Facture proforma générée et envoyée par email avec succès.');

        $this->redirectToRoute('app_home');
        }
        return $this->render('pages/explore.html.twig', [
            'form' => $form->createView(),
            'places' => $places,
        ]);
    }

    

    // routes des faq
     #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('pages/faq.html.twig');
    }

    // routes des projets
    #[Route('/projets', name: 'app_projets')]
    public function projets(EntityManagerInterface $entityManager): Response
    {
        $projets = $entityManager->getRepository(Projets::class)->findAll();
        return $this->render('pages/projets.html.twig', [
            'projets' => $projets,
        ]);
    }
    #[Route('/projets/{id}', name: 'projets_show')]
    public function show(Projets $projets): Response
    {

        return $this->render('projets/show.html.twig', [
            'projet' => $projets,
        ]);
    }

    // routes des partenaire
    #[Route('/partenaire', name: 'app_partenaire')]
    public function partenaire(EntityManagerInterface $entityManager): Response
    {
        $partenaires = $entityManager->getRepository(Partenaire::class)->findAll();
        return $this->render('pages/partenaire.html.twig', [
            'partenaires' => $partenaires,
        ]);
    }
    #[Route('/partenaire/{id}', name: 'partenaire_show')]
    public function showpartenaire(EntityManagerInterface $entityManager, Partenaire $partenaire): Response
    {
        $partenaire = $entityManager->getRepository(Partenaire::class)->find($partenaire->getId());
        return $this->render('partenaire/show.html.twig', [
            'partenaire' => $partenaire,
        ]);
    }

    
    
}
