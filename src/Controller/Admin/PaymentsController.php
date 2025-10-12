<?php
namespace App\Controller\Admin;

use App\Entity\Payments;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('ROLE_ADMIN')]
class PaymentsController extends AbstractController
{
    #[Route('/admin/payments', name: 'admin_payments_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $from   = $request->query->get('from');
        $to     = $request->query->get('to');
        $page   = max(1, (int) $request->query->get('page', 1));
        $size   = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;

        $qb = $em->getRepository(Payments::class)->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u');

        $conds = [];
        if ($q !== '') {
            $conds[] = '(LOWER(u.firstname) LIKE :q OR LOWER(u.lastname) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(p.reference) LIKE :q)';
        }
        if ($status !== '') {
            $conds[] = 'LOWER(p.status) = :status';
        }
        if ($from) {
            $conds[] = 'p.paymentdate >= :from';
        }
        if ($to) {
            $conds[] = 'p.paymentdate <= :to';
        }
        if ($conds) { $qb->where(implode(' AND ', $conds)); }
        if ($q !== '') { $qb->setParameter('q', '%'.strtolower($q).'%'); }
        if ($status !== '') { $qb->setParameter('status', strtolower($status)); }
        if ($from) { $qb->setParameter('from', new \DateTime($from.' 00:00:00')); }
        if ($to) { $qb->setParameter('to', new \DateTime($to.' 23:59:59')); }

        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(p.id)')->getQuery()->getSingleScalarResult() ?? 0);

        $items = $qb->select('p, u')
            ->orderBy('p.paymentdate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()->getResult();

        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/payments/index.html.twig', [
            'payments' => $items,
            'q' => $q,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/payments/{id}', name: 'admin_payments_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $payment = $em->getRepository(Payments::class)->find($id);
        if (!$payment) { throw $this->createNotFoundException('Paiement introuvable'); }
        return $this->render('admin/payments/show.html.twig', [ 'payment' => $payment ]);
    }

    #[Route('/admin/payments/{id}/verify', name: 'admin_payments_verify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function verify(int $id, EntityManagerInterface $em, HttpClientInterface $http, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        $payment = $em->getRepository(Payments::class)->find($id);
        if (!$payment) { throw $this->createNotFoundException('Paiement introuvable'); }
        if (!$this->isCsrfTokenValid('verify_payment_'.$payment->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? $_SERVER['FEDAPAY_SECRET_KEY'] ?? null;
        if (!$secret) {
            $this->addFlash('error', 'Clé FEDAPAY_SECRET_KEY manquante');
            return $this->redirectToRoute('admin_payments_show', ['id' => $id]);
        }
        try {
            $apiUrl = sprintf('https://api.fedapay.com/v1/transactions/%s', urlencode((string) $payment->getReference()));
            $resp = $http->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret,
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
            ]);
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                $json = $resp->toArray(false);
                $tx = $json['transaction'] ?? null;
                if ($tx && isset($tx['status'])) {
                    $payment->setStatus((string) $tx['status']);
                    if (isset($tx['amount'])) {
                        $payment->setAmount((string) number_format((float)$tx['amount'], 2, '.', ''));
                    }
                    $em->flush();
                    $this->addFlash('success', 'Paiement vérifié et mis à jour.');
                }
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Échec de vérification: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_payments_show', ['id' => $id]);
    }

    #[Route('/admin/payments/export', name: 'admin_payments_export', methods: ['GET'])]
    public function export(Request $request, EntityManagerInterface $em): Response
    {
        // Export simple CSV sur les filtres de base
        $status = trim((string) $request->query->get('status', ''));
        $from   = $request->query->get('from');
        $to     = $request->query->get('to');

        $qb = $em->getRepository(Payments::class)->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u');

        $conds = [];
        if ($status !== '') { $conds[] = 'LOWER(p.status) = :status'; }
        if ($from) { $conds[] = 'p.paymentdate >= :from'; }
        if ($to) { $conds[] = 'p.paymentdate <= :to'; }
        if ($conds) { $qb->where(implode(' AND ', $conds)); }
        if ($status !== '') { $qb->setParameter('status', strtolower($status)); }
        if ($from) { $qb->setParameter('from', new \DateTime($from.' 00:00:00')); }
        if ($to) { $qb->setParameter('to', new \DateTime($to.' 23:59:59')); }

        $rows = $qb->orderBy('p.paymentdate', 'DESC')->getQuery()->getResult();

        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Date', 'Montant', 'Statut', 'Référence', 'Email', 'Nom']);
        foreach ($rows as $p) {
            /** @var Payments $p */
            $u = $p->getUser();
            fputcsv($out, [
                $p->getPaymentdate()?->format('Y-m-d H:i:s') ?? '',
                $p->getAmount() ?? '',
                $p->getStatus() ?? '',
                $p->getReference() ?? '',
                $u?->getEmail() ?? '',
                ($u?->getFirstname().' '.$u?->getLastname()) ?: '',
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out) ?: '';

        return new Response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="payments.csv"'
            ]
        );
    }
}
