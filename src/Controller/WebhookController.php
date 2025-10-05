<?php
namespace App\Controller;

use App\Entity\Payments;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    // FedaPay webhook endpoint (configure this URL in your FedaPay dashboard)
    #[Route('/webhooks/fedapay', name: 'webhook_fedapay', methods: ['POST'])]
    public function fedapay(Request $request, EntityManagerInterface $em): Response
    {
        // NOTE: For production, verify any signature headers provided by FedaPay
        // and reject requests that are not authentic.
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid payload'], 400);
        }

        // The exact shape depends on FedaPay's webhook. We try common fields:
        $tx = $payload['transaction'] ?? $payload['data']['transaction'] ?? null;
        if (!$tx || !isset($tx['id'])) {
            return new JsonResponse(['ok' => false, 'message' => 'Missing transaction'], 400);
        }

        $txId = (string)$tx['id'];
        $status = (string)($tx['status'] ?? 'pending');

        // Find payment by reference (we stored transactionId as reference)
        $repo = $em->getRepository(Payments::class);
        $payment = $repo->findOneBy(['reference' => $txId]);
        if (!$payment) {
            // idempotent: return 200 so FedaPay doesn't retry forever
            return new JsonResponse(['ok' => true, 'message' => 'Payment not found; ignoring'], 200);
        }

        $payment->setStatus($status);
        // Optionally update amount if provided
        if (isset($tx['amount'])) {
            $amount = (string) number_format((float)$tx['amount'], 2, '.', '');
            $payment->setAmount($amount);
        }
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }
}
