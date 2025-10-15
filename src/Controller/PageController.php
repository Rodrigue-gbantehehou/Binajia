<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PageController extends AbstractController
{
    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route('/evenements', name: 'app_events')]
    public function events(): Response
    {
        return $this->render('pages/events.html.twig');
    }

    #[Route('/lieux', name: 'app_places')]
    public function places(): Response
    {
        return $this->render('pages/places.html.twig');
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
}
