<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CookieConsentController extends AbstractController
{
   
    #[Route('/cookie/accept', name: 'cookie_accept', methods: ['POST'])]
    public function accept(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'accepted']);
        $response->headers->setCookie(new Cookie(
            'cookie_consent',
            'accepted',
            strtotime('+1 year'),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX
        ));
        return $response;
    }

    #[Route('/cookie/refuse', name: 'cookie_refuse', methods: ['POST'])]
    public function refuse(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'refused']);
        $response->headers->setCookie(new Cookie(
            'cookie_consent',
            'refused',
            strtotime('+1 year'),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX
        ));
        return $response;
    }
}
