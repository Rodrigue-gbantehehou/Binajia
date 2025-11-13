<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Service\EmailService;
use App\Form\Reservation1Type;
use App\Entity\CulturalContent;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ReservationController extends AbstractController
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private EmailService $emailService
    ) {}

    #[Route('/reservation/{slug}', name: 'app_reservation_event')]
    public function reserveEvent(Request $request, EntityManagerInterface $entityManager, string $slug): Response
    {
        $evenement = $entityManager->getRepository(Evenement::class)->findOneBy(['slug' => $slug]);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $commentaire = trim($request->request->get('commentaire', ''));

            // Validation basique
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire.';
            }

            if (empty($email)) {
                $errors['email'] = 'L\'email est obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email n\'est pas valide.';
            }

            if (empty($telephone)) {
                $errors['telephone'] = 'Le téléphone est obligatoire.';
            }

            // Si pas d'erreurs, créer la réservation
            if (empty($errors)) {
                $reservation = new Reservation();
                $reservation->setNom($nom);
                $reservation->setEmail($email);
                $reservation->setTelephone($telephone);
                $reservation->setCommentaire($commentaire);
                $reservation->setEvenement($evenement);
                $reservation->setTypereservation('evenement');

                $entityManager->persist($reservation);
                $entityManager->flush();

                // Générer le PDF de confirmation
                try {
                    $timestamp = (new \DateTime())->format('YmdHis');
                    $filename = 'confirmation_reservation_' . $reservation->getId() . '.pdf';
                    $pdfPath = $this->pdfGenerator->generatePdf(
                        'reservation/reservation_confirmation_pdf.html.twig',
                        ['reservation' => $reservation],
                        $filename,
                        'A4',
                        'portrait'
                    );

                    // Stocker le chemin du PDF dans la réservation
                    $relativePath = strstr($pdfPath, '/pdf/');
                    if ($relativePath === false) {
                        $relativePath = '/pdf/' . $filename;
                    }
                    $reservation->setFacturepdf($relativePath);
                    $entityManager->flush();

                    // Préparer les données pour l'email
                    $reservationData = [
                        'id' => $reservation->getId(),
                        'nom' => $reservation->getNom(),
                        'email' => $reservation->getEmail(),
                        'telephone' => $reservation->getTelephone(),
                        'commentaire' => $reservation->getCommentaire(),
                        'typereservation' => $reservation->getTypereservation(),
                        'evenement' => $reservation->getEvenement(),
                    ];

                    // Envoyer l'email avec le PDF en pièce jointe
                    $emailSent = $this->emailService->sendReservationConfirmationEmail(
                        $email,
                        $reservationData,
                        $pdfPath
                    );

                    if ($emailSent) {
                        $this->addFlash('success', 'Votre réservation a été confirmée ! Un email avec votre confirmation PDF vous a été envoyé.');
                    } else {
                        $this->addFlash('warning', 'Votre réservation a été enregistrée, mais l\'envoi de l\'email a échoué. Vous pouvez télécharger votre confirmation depuis votre espace membre.');
                    }

                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Votre réservation a été enregistrée, mais la génération du PDF a échoué. Vous pouvez contacter le support pour obtenir votre confirmation.');
                }

                return $this->redirectToRoute('app_home');
            }
        }
        return $this->render('reservation/event_custom.html.twig', [
            'evenement' => $evenement,
            'errors' => $errors,
        ]);
    }

    #[Route('/reservation/place/{id}', name: 'app_reservation_place')]
    public function reservePlace(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $place = $entityManager->getRepository(CulturalContent::class)->find($id);

        if (!$place) {
            throw $this->createNotFoundException('Lieu touristique non trouvé');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $commentaire = trim($request->request->get('commentaire', ''));

            // Validation basique
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire.';
            }

            if (empty($email)) {
                $errors['email'] = 'L\'email est obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email n\'est pas valide.';
            }

            if (empty($telephone)) {
                $errors['telephone'] = 'Le téléphone est obligatoire.';
            }

            // Si pas d'erreurs, créer la réservation
            if (empty($errors)) {
                $reservation = new Reservation();
                $reservation->setNom($nom);
                $reservation->setEmail($email);
                $reservation->setTelephone($telephone);
                $reservation->setCommentaire($commentaire);
                $reservation->setTypereservation('visite touristique');

                $entityManager->persist($reservation);
                $entityManager->flush();

                // Générer le PDF de confirmation
                try {
                    $timestamp = (new \DateTime())->format('YmdHis');
                    $filename = 'confirmation_reservation_' . $reservation->getId() . '.pdf';
                    $pdfPath = $this->pdfGenerator->generatePdf(
                        'reservation/reservation_confirmation_pdf.html.twig',
                        ['reservation' => $reservation],
                        $filename,
                        'A4',
                        'portrait'
                    );

                    // Stocker le chemin du PDF dans la réservation
                    $relativePath = strstr($pdfPath, '/pdf/');
                    if ($relativePath === false) {
                        $relativePath = '/pdf/' . $filename;
                    }
                    $reservation->setFacturepdf($relativePath);
                    $entityManager->flush();

                    // Préparer les données pour l'email
                    $reservationData = [
                        'id' => $reservation->getId(),
                        'nom' => $reservation->getNom(),
                        'email' => $reservation->getEmail(),
                        'telephone' => $reservation->getTelephone(),
                        'commentaire' => $reservation->getCommentaire(),
                        'typereservation' => $reservation->getTypereservation(),
                    ];

                    // Envoyer l'email avec le PDF en pièce jointe
                    $emailSent = $this->emailService->sendReservationConfirmationEmail(
                        $email,
                        $reservationData,
                        $pdfPath
                    );

                    if ($emailSent) {
                        $this->addFlash('success', 'Votre réservation de visite a été confirmée ! Un email avec votre confirmation PDF vous a été envoyé.');
                    } else {
                        $this->addFlash('warning', 'Votre réservation a été enregistrée, mais l\'envoi de l\'email a échoué. Vous pouvez télécharger votre confirmation depuis votre espace membre.');
                    }

                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Votre réservation a été enregistrée, mais la génération du PDF a échoué. Vous pouvez contacter le support pour obtenir votre confirmation.');
                }

                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('reservation/place_custom.html.twig', [
            'place' => $place,
            'errors' => $errors,
        ]);
    }

    #[Route('/reservation', name: 'app_reservation')]
    public function index(EntityManagerInterface $em, Request $request, PdfGeneratorService $pdfGeneratorService, EmailService $emailService): Response
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
            // Envoyer le PDF par email
            $emailService->sendReservationConfirmation($reservation, $pdfPath);

            $this->addFlash('success', 'Facture proforma générée et envoyée par email avec succès.');
            return $this->redirectToRoute('app_home');

        }
        return $this->render('reservation/form_reservation.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
       
    }
}
