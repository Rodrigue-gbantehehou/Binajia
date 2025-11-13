<?php
namespace App\Controller;

use App\Repository\CulturalContentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PlaceController extends AbstractController
{
    #[Route('/lieux/{slug}', name: 'app_place_show')]
    public function show(string $slug, CulturalContentRepository $placeRepository): Response
    {
        $place = $placeRepository->findOneBy(['slug' => $slug]);

        return $this->render('pages/place_show.html.twig', [
            'place' => $place,
        ]);
    }
}
