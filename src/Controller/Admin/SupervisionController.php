<?php
namespace App\Controller\Admin;

use App\Entity\MembershipCards;
use App\Entity\Payments;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SupervisionController extends AbstractController
{
    private string $uploadDirectory;

    public function __construct(string $uploadDir)
    {
        $this->uploadDirectory = $uploadDir;
    }

    #[Route('/admin/supervision/health', name: 'admin_health_index', methods: ['GET'])]
    public function health(EntityManagerInterface $em): Response
    {
        $checks = [];
        // DB connectivity
        try {
            $em->getConnection()->connect();
            $checks['database'] = ['ok' => true, 'message' => 'Connexion OK'];
        } catch (\Throwable $e) {
            $checks['database'] = ['ok' => false, 'message' => $e->getMessage()];
        }

        // Writable media dirs
        $projectDir = $this->uploadDirectory;
        $dirs = [
            $projectDir . '/media/avatars',
            $projectDir . '/media/cards',
            $projectDir . '/media/receipts',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) { @mkdir($d, 0775, true); }
            $checks[$d] = ['ok' => is_writable($d), 'message' => is_writable($d) ? 'Writable' : 'Not writable'];
        }

        return $this->render('admin/supervision/health.html.twig', [
            'checks' => $checks,
        ]);
    }

    #[Route('/admin/supervision/logs', name: 'admin_logs_index', methods: ['GET'])]
    public function logs(Request $request): Response
    {
        $lines = max(50, (int) $request->query->get('lines', 200));
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev';
        $projectDir = $this->uploadDirectory;
        $logFile = $projectDir . '/var/log/' . $env . '.log';
        $content = '';
        if (is_file($logFile)) {
            $content = $this->tailFile($logFile, $lines);
        }
        return $this->render('admin/supervision/logs.html.twig', [
            'file' => $logFile,
            'content' => $content,
            'lines' => $lines,
        ]);
    }

    #[Route('/admin/supervision/metrics', name: 'admin_metrics_index', methods: ['GET'])]
    public function metrics(EntityManagerInterface $em): Response
    {
        $userRepo = $em->getRepository(User::class);
        $payRepo = $em->getRepository(Payments::class);
        $cardRepo = $em->getRepository(MembershipCards::class);
        $now = new \DateTime();
        $d30 = (clone $now)->modify('-30 days');

        $totalUsers = (int) ($userRepo->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $totalPays  = (int) ($payRepo->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $totalCards = (int) ($cardRepo->createQueryBuilder('c')->select('COUNT(c.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $cards30d   = (int) ($cardRepo->createQueryBuilder('c')->select('COUNT(c.id)')->where('c.issuedate >= :d30')->setParameter('d30', $d30)->getQuery()->getSingleScalarResult() ?? 0);

        return $this->render('admin/supervision/metrics.html.twig', [
            'totalUsers' => $totalUsers,
            'totalPayments' => $totalPays,
            'totalCards' => $totalCards,
            'cards30d' => $cards30d,
        ]);
    }

    private function tailFile(string $file, int $lines = 200): string
    {
        $data = '';
        $fp = @fopen($file, 'rb');
        if ($fp === false) { return $data; }
        $buffer = '';
        $chunkSize = 4096;
        $pos = -1;
        $lineCount = 0;
        fseek($fp, 0, SEEK_END);
        $filesize = ftell($fp);
        while ($lineCount < $lines && $pos < $filesize) {
            $pos = min($filesize, $pos + $chunkSize);
            fseek($fp, -$pos, SEEK_END);
            $buffer = fread($fp, $pos) . $buffer;
            $lineCount = substr_count($buffer, "\n");
        }
        fclose($fp);
        $parts = explode("\n", $buffer);
        $data = implode("\n", array_slice($parts, -$lines));
        return $data;
    }
}
