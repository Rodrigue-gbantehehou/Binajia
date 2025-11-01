<?php
namespace App\Controller;

use App\Entity\Projets;
use App\Entity\Evenement;
use App\Entity\CulturalContent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function indexA(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les événements triés par date de début (du plus proche au plus éloigné)
        $events = $entityManager->getRepository(Evenement::class)->findAll();

        // Trier les événements par date de début (événements à venir en premier)
        usort($events, function($a, $b) {
            $dateA = $a->getStartDate();
            $dateB = $b->getStartDate();

            // Si les deux événements ont une date
            if ($dateA && $dateB) {
                // Si les deux sont dans le futur, trier par date croissante
                if ($dateA > new \DateTime() && $dateB > new \DateTime()) {
                    return $dateA <=> $dateB;
                }
                // Si un est passé et l'autre futur, le futur en premier
                if ($dateA > new \DateTime() && $dateB <= new \DateTime()) {
                    return -1;
                }
                if ($dateA <= new \DateTime() && $dateB > new \DateTime()) {
                    return 1;
                }
                // Les deux passés : trier par date décroissante (plus récent en premier)
                return $dateB <=> $dateA;
            }

            // Si un événement n'a pas de date, le mettre à la fin
            if (!$dateA && $dateB) return 1;
            if ($dateA && !$dateB) return -1;

            return 0;
        });
        $projets = $entityManager->getRepository(Projets::class)->findAll();

        $places = $entityManager->getRepository(CulturalContent::class)->findAll();

        // Render the redesigned homepage by default
        return $this->render('home/index_c.html.twig', compact('places','events','projets'));
    }

    #[Route('/home/b', name: 'app_home_b')]
    public function indexB(): Response
    {
        $events = [];
        $places = [];
        $stories = [
            ['id'=>1,'title'=>'Textiles yoruba: art et identité','excerpt'=>'Des motifs au sens profond...'],
            ['id'=>2,'title'=>'Bronzes du Bénin: héritage','excerpt'=>'Une histoire millénaire...'],
        ];
        return $this->render('home/index_b.html.twig', compact('events','places','stories'));
    }

    #[Route('/home/c', name: 'app_home_c')]
    public function indexC(): Response
    {
        // Use demo events for now to avoid database issues
        $events = [
            [
                'title' => 'Festival Bénin–Nigéria',
                'startDate' => new \DateTime('+7 days'),
                'location' => 'Cotonou',
                'country' => 'Bénin'
            ],
            [
                'title' => 'Soirée Highlife & Afrobeat',
                'startDate' => new \DateTime('+14 days'),
                'location' => 'Lagos',
                'country' => 'Nigeria'
            ],
            [
                'title' => 'Visite guidée de Lagos',
                'startDate' => new \DateTime('+21 days'),
                'location' => 'Lagos',
                'country' => 'Nigeria'
            ],
            [
                'title' => 'Promotion hôtelière spéciale',
                'startDate' => new \DateTime('+30 days'),
                'location' => 'Bénin & Nigéria',
                'country' => 'Bénin & Nigéria'
            ],
            [
                'title' => 'Festival des Arts Contemporains',
                'startDate' => new \DateTime('+45 days'),
                'location' => 'Porto-Novo',
                'country' => 'Bénin'
            ]
        ];

        $places = [];
        return $this->render('home/index_c.html.twig', compact('events', 'places'));
    }

    #[Route('/homepage', name: 'app_homepage')]
    public function homepage(): Response
    {
        // Page d'accueil principale avec Twig
        return $this->render('home/index.html.twig');
    }

    #[Route('/homepage-full', name: 'app_homepage_full')]
    public function homepageFull(): Response
    {
        // Page d'accueil complète HTML
        return $this->render('home/homepage_full.html.twig');
    }
}
