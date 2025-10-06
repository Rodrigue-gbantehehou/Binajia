<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SectionsController extends AbstractController
{
    #[Route('/admin/members', name: 'admin_members_index')]
    public function members(): Response { return $this->render('admin/section.html.twig', ['title' => 'Membres', 'subtitle' => 'Gestion des membres']); }

    #[Route('/admin/cards', name: 'admin_cards_index')]
    public function cards(): Response { return $this->render('admin/section.html.twig', ['title' => 'Cartes', 'subtitle' => 'Gestion des cartes membres']); }

    #[Route('/admin/payments', name: 'admin_payments_index')]
    public function payments(): Response { return $this->render('admin/section.html.twig', ['title' => 'Paiements', 'subtitle' => 'Suivi et encaissements']); }

    #[Route('/admin/receipts', name: 'admin_receipts_index')]
    public function receipts(): Response { return $this->render('admin/section.html.twig', ['title' => 'Reçus', 'subtitle' => 'Génération et export des reçus']); }

    #[Route('/admin/events', name: 'admin_events_index')]
    public function events(): Response { return $this->render('admin/section.html.twig', ['title' => 'Événements', 'subtitle' => 'Gestion des événements']); }

    #[Route('/admin/places', name: 'admin_places_index')]
    public function places(): Response { return $this->render('admin/section.html.twig', ['title' => 'Lieux', 'subtitle' => 'Gestion des lieux']); }

    #[Route('/admin/reservations', name: 'admin_reservations_index')]
    public function reservations(): Response { return $this->render('admin/section.html.twig', ['title' => 'Réservations', 'subtitle' => 'Suivi des réservations']); }

    #[Route('/admin/supervision/health', name: 'admin_health_index')]
    public function health(): Response { return $this->render('admin/section.html.twig', ['title' => 'Health Checks', 'subtitle' => 'État des services']); }

    #[Route('/admin/supervision/logs', name: 'admin_logs_index')]
    public function logs(): Response { return $this->render('admin/section.html.twig', ['title' => 'Logs', 'subtitle' => 'Derniers journaux']); }

    #[Route('/admin/supervision/metrics', name: 'admin_metrics_index')]
    public function metrics(): Response { return $this->render('admin/section.html.twig', ['title' => 'Métriques', 'subtitle' => 'Indicateurs et mesures']); }

    #[Route('/admin/security/users', name: 'admin_security_users')]
    public function securityUsers(): Response { return $this->render('admin/section.html.twig', ['title' => 'Utilisateurs', 'subtitle' => 'Gestion des comptes']); }

    #[Route('/admin/security/roles', name: 'admin_security_roles')]
    public function securityRoles(): Response { return $this->render('admin/section.html.twig', ['title' => 'Rôles', 'subtitle' => 'Hiérarchie et autorisations']); }

    #[Route('/admin/security/2fa', name: 'admin_security_2fa')]
    public function security2fa(): Response { return $this->render('admin/section.html.twig', ['title' => '2FA', 'subtitle' => 'Authentification à deux facteurs']); }

    #[Route('/admin/audit', name: 'admin_audit_index')]
    public function audit(): Response { return $this->render('admin/section.html.twig', ['title' => 'Audit', 'subtitle' => 'Historique des actions']); }

    #[Route('/admin/settings', name: 'admin_settings_index')]
    public function settings(): Response { return $this->render('admin/section.html.twig', ['title' => 'Réglages', 'subtitle' => 'Paramètres de l’application']); }

    #[Route('/admin/api', name: 'admin_api_index')]
    public function api(): Response { return $this->render('admin/section.html.twig', ['title' => 'API', 'subtitle' => 'Intégrations et webhooks']); }
}
