<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Entity\CulturalContent;
use App\Service\PdfGeneratorService;
use Symfony\Component\Intl\Countries;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/evenements', name: 'app_events')]
    public function events(EntityManagerInterface $entityManager): Response
    {
        $evenements = $entityManager->getRepository(Evenement::class)->findBy([], ['startDate' => 'ASC']);

        return $this->render('pages/events.html.twig', [
            'evenements' => $evenements,
        ]);
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

        return $this->render('pages/membership.html.twig', [
            'countries' => $countries,
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
    public function avantage(Request $request, EntityManagerInterface $em, PdfGeneratorService $pdfGeneratorService): Response
    {
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
             $this->addFlash('success', 'Facture proforma générée avec succès.');

            return $this->render('reservation/facture_proforma.html.twig', [
                'reservation' => $reservation,
            ]);
        }
        return $this->render('pages/avantage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    //page partenaire
    #[Route('/partenaire', name: 'app_partenaire')]
    public function partenaire(): Response
    {
        return $this->render('pages/partenaire.html.twig');
    }
}
