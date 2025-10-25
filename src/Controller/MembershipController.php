<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class MembershipController extends AbstractController
{
    #[Route('/membership', name: 'app_members_area')]
    public function index(): Response
    {
        $user = (object) [
            'firstName' => 'Membre',
            'lastName' => 'BINAJIA',
            'memberId' => 'BjNg-2025-XXX',
            'country' => '—',
            'avatarUrl' => '/media/avatar.png',
            'joinedAt' => new \DateTime('now')
        ];
        return $this->render('membership/index.html.twig', compact('user'));
    }

    #[Route('/membership/card', name: 'app_membership_card')]
    public function card(): Response
    {
        $user = (object) [
            'firstName' => 'Membre',
            'lastName' => 'BINAJIA',
            'memberId' => 'BjNg-2025-XXX',
            'country' => '—',
            'avatarUrl' => '/media/avatar.png',
            'joinedAt' => new \DateTime('now')
        ];
        return $this->render('membership/card.html.twig', compact('user'));
    }

    #[Route('/cards/{filename}', name: 'app_card_pdf', requirements: ['filename' => '.+\.pdf'])]
    public function serveCardPdf(string $filename): BinaryFileResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/var/uploads/cards/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable');
        }

        return new BinaryFileResponse($filePath);
    }
}
