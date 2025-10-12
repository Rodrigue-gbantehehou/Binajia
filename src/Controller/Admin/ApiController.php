<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ApiController extends AbstractController
{
    #[Route('/admin/api', name: 'admin_api_index', methods: ['GET'])]
    public function index(): Response
    {
        // Placeholder API configuration data
        $apiConfig = [
            'endpoints' => [
                ['name' => 'Members API', 'url' => '/api/members', 'method' => 'GET', 'status' => 'active'],
                ['name' => 'Payments Webhook', 'url' => '/api/webhooks/fedapay', 'method' => 'POST', 'status' => 'active'],
                ['name' => 'Cards API', 'url' => '/api/cards', 'method' => 'GET', 'status' => 'inactive'],
            ],
            'webhooks' => [
                ['name' => 'FedaPay Payment', 'url' => 'https://api.fedapay.com/webhooks', 'events' => ['payment.succeeded', 'payment.failed']],
                ['name' => 'Email Service', 'url' => 'https://hooks.slack.com/services/...', 'events' => ['user.registered']],
            ],
            'apiKeys' => [
                ['name' => 'Mobile App', 'key' => 'bja_***************', 'created' => new \DateTime('-30 days'), 'lastUsed' => new \DateTime('-2 hours')],
                ['name' => 'Web Dashboard', 'key' => 'bja_***************', 'created' => new \DateTime('-60 days'), 'lastUsed' => new \DateTime('-1 hour')],
            ],
        ];

        return $this->render('admin/api/index.html.twig', [
            'apiConfig' => $apiConfig,
        ]);
    }
}
