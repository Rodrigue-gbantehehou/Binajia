<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    #[Route('/admin/audit', name: 'admin_audit_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Placeholder audit data - replace with real audit log entity when available
        $auditLogs = [
            [
                'id' => 1,
                'user' => 'admin@binajia.org',
                'action' => 'Création utilisateur',
                'target' => 'User #123',
                'ip' => '192.168.1.100',
                'timestamp' => new \DateTime('-2 hours'),
                'details' => 'Nouvel utilisateur créé: jean.dupont@example.com',
            ],
            [
                'id' => 2,
                'user' => 'admin@binajia.org',
                'action' => 'Modification carte',
                'target' => 'Card #456',
                'ip' => '192.168.1.100',
                'timestamp' => new \DateTime('-4 hours'),
                'details' => 'Carte régénérée pour l\'utilisateur ID 123',
            ],
            [
                'id' => 3,
                'user' => 'manager@binajia.org',
                'action' => 'Vérification paiement',
                'target' => 'Payment #789',
                'ip' => '192.168.1.101',
                'timestamp' => new \DateTime('-6 hours'),
                'details' => 'Vérification FedaPay effectuée',
            ],
        ];

        return $this->render('admin/audit/index.html.twig', [
            'auditLogs' => $auditLogs,
            'total' => count($auditLogs),
        ]);
    }
}
