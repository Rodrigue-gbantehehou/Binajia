<?php
namespace App\Controller\Admin;

use App\Entity\Receipts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReceiptsController extends AbstractController
{
    #[Route('/admin/receipts', name: 'admin_receipts_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $page   = max(1, (int) $request->query->get('page', 1));
        $size   = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;

        $qb = $em->getRepository(Receipts::class)->createQueryBuilder('r')
            ->leftJoin('r.payment', 'p')->addSelect('p')
            ->leftJoin('p.user', 'u')->addSelect('u');

        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(r.id)')->getQuery()->getSingleScalarResult() ?? 0);

        $items = $qb->select('r, p, u')
            ->orderBy('r.issued_date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()->getResult();

        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/receipts/index.html.twig', [
            'receipts' => $items,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/receipts/{id}/download', name: 'admin_receipts_download', methods: ['GET'])]
    public function download(int $id, EntityManagerInterface $em): Response
    {
        $receipt = $em->getRepository(Receipts::class)->find($id);
        if (!$receipt) { throw $this->createNotFoundException('Reçu introuvable'); }
        $url = $receipt->getPdfurl();
        if (!$url) { throw $this->createNotFoundException('PDF manquant'); }
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $path = $projectDir . $url;
        if (!is_file($path)) { throw $this->createNotFoundException('Fichier non trouvé'); }
        return new BinaryFileResponse($path);
    }
}
