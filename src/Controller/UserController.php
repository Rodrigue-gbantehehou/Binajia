<?php
namespace App\Controller;

use App\Entity\Payments;
use App\Entity\Receipts;
use App\Entity\MembershipCards;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    private string $uploadDirectory;

    public function __construct(string $uploadDir)
    {
        $this->uploadDirectory = $uploadDir;
    }
    #[Route('/dashboard', name: 'app_user_dashboard')]
    public function dashboardA(EntityManagerInterface $em): Response
    {
        $authUser = $this->getUser();
        if (!$authUser) {
            return $this->redirectToRoute('app_login');
        }

        // Ensure user has required methods before using them
        if (!method_exists($authUser, 'getFirstname') || !method_exists($authUser, 'getLastname') ||
            !method_exists($authUser, 'getCountry')) {
            return $this->redirectToRoute('app_login');
        }

        // Build memberId and avatar URL
        $memberId = sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $authUser->getId());
        $avatarUrl = sprintf('/media/avatars/%d.jpg', $authUser->getId());
        if (!file_exists($this->uploadDirectory . $avatarUrl)) {
            $avatarUrl = '/media/avatar.png';
        }

        // Payments list (latest first)
        $payRepo = $em->getRepository(Payments::class);
        $userPays = $payRepo->findBy(['user' => $authUser], ['paymentdate' => 'DESC']);
        $payments = [];
        foreach ($userPays as $p) {
            $payments[] = [
                'id' => $p->getId(),
                'date' => $p->getPaymentdate() ?? new \DateTime(),
                'label' => 'Cotisation annuelle',
                'amount' => $p->getAmount(),
                'currency' => 'XOF',
            ];
        }

        // Last payment (if any)
        $lastPayment = $payments[0] ?? null;

        // Membership card history (latest first)
        $cardRepo = $em->getRepository(MembershipCards::class);
        $cards = $cardRepo->findBy(['user' => $authUser], ['issuedate' => 'DESC']);
        $cardPdf = null;
        if (!empty($cards)) {
            $first = $cards[0] ?? null;
            if ($first) {
                $cardPdf = $first->getPdfurl();
            }
        }

        // Receipts for download (map by payment id)
        $rcRepo = $em->getRepository(Receipts::class);
        $receiptsByPayment = [];
        foreach ($rcRepo->findAll() as $rc) {
            $pid = $rc->getPayment()?->getId();
            if ($pid) {
                $receiptsByPayment[$pid] = $rc->getPdfurl();
            }
        }

        //carte actuelle 
        $carteActuele= $em->getRepository(MembershipCards::class)->findOneBy(['user' => $authUser]);

        // View model for template
        $user = (object) [
            'firstName' => $authUser->getFirstname(),
            'lastName' => $authUser->getLastname(),
            'memberId' => $memberId,
            'country' => $authUser->getCountry(),
            'avatarUrl' => $avatarUrl,
            'joinedAt' => method_exists($authUser, 'getCreatedAt') ? $authUser->getCreatedAt() : new \DateTime(),
            'cardPdf' => $cardPdf,
            'receipts' => $receiptsByPayment,
            'carteActuele' => $carteActuele,
            'email' => $authUser->getEmail(),
        ];

        $tickets = [];

        return $this->render('user/dashboard_a.html.twig', [
            'payments' => $payments,
            'tickets' => $tickets,
            'lastPayment' => $lastPayment,
            'user' => $user,
            'cards' => $cards,
            'carteActuele' => $carteActuele,
        ]);
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

    #[Route('/mes-reservations', name: 'app_user_reservations')]
    public function myReservations(EntityManagerInterface $em): Response
    {
        $authUser = $this->getUser();
        if (!$authUser) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les réservations de l'utilisateur par son email
        $reservationRepo = $em->getRepository(Reservation::class);
        $reservations = $reservationRepo->findBy(['email' => $authUser->getEmail()], ['id' => 'DESC']);

        return $this->render('user/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
