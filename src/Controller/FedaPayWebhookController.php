<?php

namespace App\Controller;

use App\Entity\Don;
use App\Service\MailerService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FedaPayWebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private MailerService $mailerService,
        private PdfGeneratorService $pdfGeneratorService
    ) {}

    #[Route('/webhook', name: 'app_webhook_fedapay', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        // Log the raw payload for debugging
        $rawPayload = $request->getContent();
        $this->logger->info('Webhook FedaPay received', ['payload' => $rawPayload]);

        $payload = json_decode($rawPayload, true);

        if (!$payload || !isset($payload['event'])) {
            $this->logger->warning('Webhook FedaPay: invalid payload', ['payload' => $payload]);
            return new Response('Invalid payload', 400);
        }

        $eventName = $payload['event']['name'];
        $tx = $payload['data']['object'] ?? null;

        if (!$tx || !isset($tx['id'])) {
            $this->logger->warning('Webhook FedaPay: missing transaction data', ['payload' => $payload]);
            return new Response('Invalid transaction data', 400);
        }

        $transactionId = $tx['id'];
        $status = strtolower($tx['status'] ?? 'unknown');
        $amount = isset($tx['amount']) ? $tx['amount'] / 100 : null; // Convert cents to base unit

        $this->logger->info('Webhook FedaPay processing', [
            'event' => $eventName,
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount
        ]);

        try {
            // Find the corresponding donation
            $don = $this->em->getRepository(Don::class)->findOneBy(['transactionId' => $transactionId]);
            
            if (!$don) {
                $this->logger->warning('Webhook FedaPay: donation not found', ['transaction_id' => $transactionId]);
                
                // You might want to create a new donation if not found, depending on your workflow
                return new Response('Donation not found', 404);
            }

            $oldStatus = $don->getStatut();
            
            // Update donation status
            $don->setStatut($status);
            $don->setUpdatedAt(new \DateTimeImmutable());

            // Update amount if provided and different
            if ($amount && $amount != $don->getMontant()) {
                $don->setMontant(number_format($amount, 2, '.', ''));
            }

            $this->em->flush();

            // Handle successful payment
            if (in_array($status, ['approved', 'succeeded', 'success', 'paid']) && 
                !in_array($oldStatus, ['approved', 'succeeded', 'success', 'paid'])) {
                
                $this->handleSuccessfulPayment($don);
            }

            // Handle failed/cancelled payment
            if (in_array($status, ['canceled', 'failed', 'declined']) && 
                !in_array($oldStatus, ['canceled', 'failed', 'declined'])) {
                
                $this->handleFailedPayment($don);
            }

            $this->logger->info('Webhook FedaPay: status updated successfully', [
                'don_id' => $don->getId(),
                'transaction_id' => $transactionId,
                'old_status' => $oldStatus,
                'new_status' => $status
            ]);

            return new Response('OK', 200);

        } catch (\Throwable $e) {
            $this->logger->error('Webhook FedaPay: processing error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new Response('Server error', 500);
        }
    }

    private function handleSuccessfulPayment(Don $don): void
    {
        try {
            // Generate and send receipt for non-anonymous donors
            if (!in_array(strtolower($don->getNom()), ['donateur anonyme', 'anonyme'])) {
                $this->generateAndSendReceipt($don);
            }

            // You can also trigger other actions here:
            // - Send thank you email
            // - Update statistics
            // - Notify administrators
            // - etc.

            $this->logger->info('Successful payment processed', [
                'don_id' => $don->getId(),
                'email' => $don->getEmail()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Error processing successful payment', [
                'don_id' => $don->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleFailedPayment(Don $don): void
    {
        try {
            // Handle failed payments (optional)
            // - Send failure notification
            // - Update internal records
            // - etc.

            $this->logger->info('Failed payment processed', [
                'don_id' => $don->getId(),
                'email' => $don->getEmail()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Error processing failed payment', [
                'don_id' => $don->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateAndSendReceipt(Don $don): void
    {
        try {
            $receiptPdfPath = $this->getParameter('kernel.project_dir') . '/var/receipts/' . $don->getId() . '.pdf';
            $receiptFileName = 'reçu_' . $don->getId() . '.pdf';

            // Generate PDF receipt
            $htmlContent = $this->renderView('don/receipt.html.twig', [
                'don' => $don,
                'page_title' => 'Reçu de don Binajia',
                'meta_description' => 'Reçu de don Binajia'
            ]);

            // Send email with receipt
            $this->mailerService->sendDonationReceipt(
                $don->getEmail(),
                'Votre reçu de don Binajia - Merci !',
                $htmlContent,
                $receiptPdfPath,
                $receiptFileName
            );

            $this->logger->info('Receipt sent successfully', ['don_id' => $don->getId()]);

        } catch (\Throwable $e) {
            $this->logger->error('Error sending receipt', [
                'don_id' => $don->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}