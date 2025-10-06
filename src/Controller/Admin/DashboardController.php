<?php
namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Entity\MembershipCards;
use App\Entity\Payments;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $now = new \DateTime();
        $d30 = (clone $now)->modify('-30 days');

        // Repositories
        $userRepo = $em->getRepository(User::class);
        $payRepo = $em->getRepository(Payments::class);
        $cardRepo = $em->getRepository(MembershipCards::class);
        $evtRepo = $em->getRepository(Evenement::class);

        // Totals
        $totalMembers = (int) ($userRepo->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $newMembers30d = (int) ($userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :d30')
            ->setParameter('d30', $d30)
            ->getQuery()->getSingleScalarResult() ?? 0);

        // Events upcoming (startDate >= now)
        $eventsUpcoming = (int) ($evtRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.startDate IS NOT NULL AND e.startDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()->getSingleScalarResult() ?? 0);

        // Revenue totals (DECIMAL string -> cast to float for display)
        $sumAll = $payRepo->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amount), 0) as total')
            ->getQuery()->getSingleScalarResult();
        $sum30 = $payRepo->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amount), 0) as total')
            ->where('p.paymentdate >= :d30')
            ->setParameter('d30', $d30)
            ->getQuery()->getSingleScalarResult();
        $revenueTotal = (string) ($sumAll ?? '0');
        $revenue30d = (string) ($sum30 ?? '0');

        // Cards generated last 30 days
        $cards30d = (int) ($cardRepo->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.issuedate >= :d30')
            ->setParameter('d30', $d30)
            ->getQuery()->getSingleScalarResult() ?? 0);

        // Activities: latest 5 payments
        $latestPays = $payRepo->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.paymentdate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();
        $activities = [];
        foreach ($latestPays as $p) {
            /** @var Payments $p */
            $u = $p->getUser();
            $activities[] = [
                'name' => $u ? ($u->getFirstname().' '.$u->getLastname()) : '—',
                'email' => $u?->getEmail() ?? '—',
                'avatar' => '/media/avatar.png',
                'action' => 'Paiement de cotisation',
                'date' => $p->getPaymentdate()?->format('d/m/Y H:i') ?? '—',
                'status' => 'Complété',
            ];
        }

        // Stats for template
        $stats = [
            'totalMembers' => $totalMembers,
            'membersGrowth' => sprintf('+%d ce mois', $newMembers30d),
            'eventsCount' => $eventsUpcoming,
            'newEvents' => '+0 nouveaux',
            'revenueTotal' => $revenueTotal.' XOF',
            'revenueGrowth' => $revenue30d.' sur 30j',
        ];

        // Some KPI tiles (optional small grid)
        $kpis = [
            ['label' => 'Revenus 30j', 'value' => $revenue30d.' XOF'],
            ['label' => 'Nouveaux membres', 'value' => (string) $newMembers30d],
            ['label' => 'Cartes générées (30j)', 'value' => (string) $cards30d],
            ['label' => 'Événements à venir', 'value' => (string) $eventsUpcoming],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'kpis' => $kpis,
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }
}
