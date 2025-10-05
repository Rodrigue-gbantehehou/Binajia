<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlaceController extends AbstractController
{
    #[Route('/lieux/{slug}', name: 'app_place_show')]
    public function show(string $slug): Response
    {
        $place = [
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'city' => 'Lagos',
            'country' => 'Nigeria',
            'image' => 'https://images.unsplash.com/photo-1590736969955-71cc94901144?q=80&w=1400&auto=format&fit=crop',
            'description' => "Présentation du lieu et informations pratiques."
        ];

        return $this->render('pages/place_show.html.twig', [
            'place' => (object)$place,
        ]);
    }
}
