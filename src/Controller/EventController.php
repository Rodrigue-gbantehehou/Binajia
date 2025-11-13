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
    public function show(string $slug, EntityManagerInterface $entityManager): Response
    {
            
        $event = $entityManager->getRepository(Evenement::class)->findOneBy(['title' => $slug]);
        return $this->render('pages/event_show.html.twig', [
            'event' => $event,
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
