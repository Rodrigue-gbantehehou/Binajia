<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_mentions')]
    public function mentions(): Response { return $this->render('legal/mentions.html.twig'); }

    #[Route('/cgu', name: 'app_legal_cgu')]
    public function cgu(): Response { return $this->render('legal/cgu.html.twig'); }

    #[Route('/confidentialite', name: 'app_legal_privacy')]
    public function privacy(): Response { return $this->render('legal/privacy.html.twig'); }

    #[Route('/engagement-ethique', name: 'app_engagement_ethique')]
    public function engagement(): Response { return $this->render('legal/engagement.html.twig'); }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response { return $this->render('legal/faq.html.twig'); }
}
