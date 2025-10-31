<?php
namespace App\Controller;

use App\Entity\Evenement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EventController extends AbstractController
{
    #[Route('/evenements/{slug}', name: 'app_event_show')]
    public function show(string $slug): Response
    {
        // Demo data – replace by repository lookup later
        $event = [
            'title' => 'Événement: '.ucwords(str_replace('-', ' ', $slug)),
            'datetime' => '10 novembre 2025 à 18:00',
            'location' => 'Ouidah, Bénin',
            'price' => '5 000 FCFA',
            'image' => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?q=80&w=1400&auto=format&fit=crop',
            'description' => "Description de l'événement à venir."
        ];

        return $this->render('pages/event_show.html.twig', [
            'event' => (object)$event,
        ]);
    }

    #[Route('/evenements', name: 'app_events')]
    public function events(EntityManagerInterface $entityManager): Response
    {
        $evenements = $entityManager->getRepository(Evenement::class)->findBy([], ['startDate' => 'ASC']);

        return $this->render('pages/events.html.twig', [
            'evenements' => $evenements,
        ]);
    }

}
