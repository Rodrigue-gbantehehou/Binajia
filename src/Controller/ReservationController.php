<?php

namespace App\Controller;

use App\Entity\CulturalContent;
use App\Entity\Evenement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    #[Route('/reservation/{id}', name: 'app_reservation_event')]
    public function reserveEvent(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);

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

                // Déterminer automatiquement le type de réservation
                $reservation->setTypereservation('evenement');

                $entityManager->persist($reservation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');

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

                // Déterminer automatiquement le type de réservation
                $reservation->setTypereservation('visite touristique');

                $entityManager->persist($reservation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre réservation de visite a été enregistrée avec succès !');

                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('reservation/place_custom.html.twig', [
            'place' => $place,
            'errors' => $errors,
        ]);
    }

    #[Route('/reservation', name: 'app_reservation')]
    public function index(): Response
    {
        return $this->render('reservation/index.html.twig', [
            'controller_name' => 'ReservationController',
        ]);
    }
}
