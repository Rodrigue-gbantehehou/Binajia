<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/dashboard', name: 'app_user_dashboard')]
    public function dashboardA(): Response
    {
        $user = (object)[
            'firstName' => 'Ayo',
            'lastName' => 'Mensah',
            'memberId' => 'BJ-2025-001',
            'country' => 'Bénin',
            'avatarUrl' => '/media/avatar.png',
            'joinedAt' => new \DateTime('-5 months')
        ];
        $payments = [
            ['id'=>1,'date'=>new \DateTime('-20 days'),'label'=>'Cotisation annuelle','amount'=>25,'currency'=>'EUR'],
        ];
        $tickets = [
            ['id'=>11,'eventTitle'=>'Soirée Highlife','date'=>new \DateTime('+10 days')],
        ];
        $lastPayment = $payments[0] ?? null;
        return $this->render('user/dashboard_a.html.twig', compact('payments','tickets','lastPayment','user'));
    }

    #[Route('/dashboard/b', name: 'app_user_dashboard_b')]
    public function dashboardB(): Response
    {
        $user = (object)[
            'firstName' => 'Ayo',
            'lastName' => 'Mensah',
            'memberId' => 'BJ-2025-001',
            'country' => 'Bénin',
            'avatarUrl' => '/media/avatar.png',
            'joinedAt' => new \DateTime('-5 months')
        ];
        $payments = [
            ['id'=>1,'date'=>new \DateTime('-20 days'),'label'=>'Cotisation annuelle','amount'=>25,'currency'=>'EUR'],
        ];
        $tickets = [
            ['id'=>11,'eventTitle'=>'Soirée Highlife','date'=>new \DateTime('+10 days')],
        ];
        $lastPayment = $payments[0] ?? null;
        return $this->render('user/dashboard_b.html.twig', compact('payments','tickets','lastPayment','user'));
    }
}
