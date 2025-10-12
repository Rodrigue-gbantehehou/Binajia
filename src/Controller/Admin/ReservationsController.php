<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReservationsController extends AbstractController
{
    #[Route('/admin/reservations', name: 'admin_reservations_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Placeholder data - replace with real entity when available
        $reservations = [
            [
                'id' => 1,
                'event' => 'Conférence Binajia 2025',
                'user' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'date' => new \DateTime('2025-01-15'),
                'status' => 'confirmed',
                'seats' => 2,
            ],
            [
                'id' => 2,
                'event' => 'Atelier Culturel',
                'user' => 'Marie Martin',
                'email' => 'marie@example.com',
                'date' => new \DateTime('2025-01-20'),
                'status' => 'pending',
                'seats' => 1,
            ],
        ];

        return $this->render('admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'total' => count($reservations),
        ]);
    }

    #[Route('/admin/reservations/export', name: 'admin_reservations_export', methods: ['GET'])]
    public function export(): Response
    {
        // Placeholder CSV export
        $csv = "Date,Événement,Utilisateur,Email,Places,Statut\n";
        $csv .= "15/01/2025,Conférence Binajia 2025,Jean Dupont,jean@example.com,2,Confirmé\n";
        $csv .= "20/01/2025,Atelier Culturel,Marie Martin,marie@example.com,1,En attente\n";

        return new Response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="reservations.csv"'
            ]
        );
    }
}
