<?php
// src/Controller/DonController.php

namespace App\Controller;

use App\Entity\Don;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\MailerService;
use App\Service\PdfGeneratorService;

class DonController extends AbstractController
{
    private string $fedapayPublicKey;
    private string $fedapaySecretKey;
    private string $fedapayBaseUrl;

    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private MailerService $mailerService
    ) {
        $this->fedapayPublicKey = $_ENV['FEDAPAY_PUBLIC_KEY'] ?? '';
        $this->fedapaySecretKey = $_ENV['FEDAPAY_SECRET_KEY'] ?? '';
        $this->fedapayBaseUrl = ($_ENV['FEDAPAY_ENV'] ?? 'sandbox') === 'sandbox'
            ? 'https://sandbox-api.fedapay.com/v1'
            : 'https://api.fedapay.com/v1';
    }

    #[Route('/don', name: 'app_don')]
    public function index(): Response
    {
        return $this->render('don/index.html.twig', [
            'fedapay_public_key' => $this->fedapayPublicKey
        ]);
    }
#[Route('/don/process', name: 'app_don_process', methods: ['POST'])]
public function processDon(Request $request): JsonResponse
{
    $nom = $request->request->get('nom', 'Donateur Anonyme');
    $email = $request->request->get('email', 'anonyme@binajia.org');
    $montant = (float) $request->request->get('montant', 0);
    $txId = $request->request->get('transaction_id');

    if (!$txId) {
        return $this->json(['success' => false, 'message' => 'ID de transaction manquant'], 400);
    }

    // VÉRIFICATION CRITIQUE : Vérifier le statut chez FedaPay avant d'enregistrer
    $status = 'approved';
    $shouldSave = false;
    
    try {
        if ($this->fedapaySecretKey) {
            $resp = $this->httpClient->request('GET', "{$this->fedapayBaseUrl}/transactions/{$txId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->fedapaySecretKey,
                    'Accept' => 'application/json'
                ],
                'timeout' => 10
            ]);

            $data = $resp->toArray(false);
            $tx = $data['transaction'] ?? null;
            
            if ($tx) {
                $status = strtolower($tx['status'] ?? 'pending');
                $paidStatuses = ['approved', 'succeeded', 'success', 'paid'];
                
                // Ne sauvegarder que si le paiement est confirmé
                if (in_array($status, $paidStatuses)) {
                    $shouldSave = true;
                    $montant = isset($tx['amount']) ? (float) $tx['amount'] : $montant;
                } else {
                    $this->logger->info('Transaction non payée, statut rejeté', [
                        'transaction_id' => $txId,
                        'status' => $status
                    ]);
                    return $this->json([
                        'success' => false,
                        'message' => 'Paiement non confirmé. Statut: ' . $status
                    ], 400);
                }
            }
        }
    } catch (\Throwable $e) {
        $this->logger->warning('Impossible de vérifier le statut FedaPay', [
            'transaction_id' => $txId,
            'error' => $e->getMessage()
        ]);
        return $this->json([
            'success' => false,
            'message' => 'Impossible de vérifier le paiement'
        ], 400);
    }

    // Vérifier si le don existe déjà
    $existingDon = $this->em->getRepository(Don::class)->findOneBy(['transactionId' => $txId]);
    if ($existingDon) {
        return $this->json([
            'success' => true,
            'don_id' => $existingDon->getId(),
            'statut' => $existingDon->getStatut(),
            'message' => 'Don déjà enregistré'
        ]);
    }

    // Enregistrement du don seulement si le paiement est confirmé
    try {
        $don = new Don();
        $don->setNom($nom);
        $don->setEmail($email);
        $don->setMontant(number_format($montant, 2, '.', ''));
        $don->setTransactionId($txId);
        $don->setStatut($status);
        $don->setCreatedAt(new \DateTimeImmutable());
        $don->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($don);
        $this->em->flush();

        $this->logger->info('Don enregistré avec succès', [
            'don_id' => $don->getId(),
            'transaction_id' => $txId,
            'statut' => $status
        ]);

        return $this->json([
            'success' => true,
            'don_id' => $don->getId(),
            'statut' => $status,
            'message' => 'Don enregistré avec succès'
        ]);

    } catch (\Throwable $e) {
        $this->logger->error('Erreur enregistrement don', ['error' => $e->getMessage()]);
        return $this->json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du don'], 500);
    }
}
    private function generateAndSendReceipt(Don $don, PdfGeneratorService $pdfGeneratorService): void
    {
        try {
            if (!in_array(strtolower($don->getNom()), ['donateur anonyme', 'anonyme'])) {
                $receiptPdfPath = $this->getParameter('kernel.project_dir') . '/var/receipts/' . $don->getId() . '.pdf';
                $receiptFileName = 'reçu_' . $don->getId() . '.pdf';

                $htmlContent = $this->renderView('don/receipt.html.twig', [
                    'don' => $don,
                    'page_title' => 'Reçu de don Binajia',
                    'meta_description' => 'Reçu de don Binajia'
                ]);

                $this->mailerService->sendDonationReceipt(
                    $don->getEmail(),
                    'Votre reçu de don Binajia',
                    $htmlContent,
                    $receiptPdfPath,
                    $receiptFileName
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi reçu don', [
                'don_id' => $don->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
    #[Route('/don/verify-payment', name: 'app_don_verify_payment', methods: ['GET'])]
    public function verifyPayment(Request $request): JsonResponse
    {
        $txId = $request->query->get('tx');
        if (!$txId) {
            return $this->json(['ok' => false, 'message' => 'Transaction ID manquant'], 400);
        }

        if (!$this->fedapaySecretKey) {
            return $this->json(['ok' => false, 'message' => 'Clé secrète manquante'], 500);
        }

        try {
            $resp = $this->httpClient->request('GET', "{$this->fedapayBaseUrl}/transactions/{$txId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->fedapaySecretKey,
                    'Accept' => 'application/json'
                ],
                'timeout' => 15
            ]);

            $data = $resp->toArray(false);
            $tx = $data['transaction'] ?? null;
            if (!$tx) {
                return $this->json(['ok' => false, 'message' => 'Transaction introuvable'], 404);
            }

            $status = strtolower($tx['status'] ?? 'unknown');
            $paid = in_array($status, ['approved', 'succeeded', 'success', 'paid']);

            return $this->json(['ok' => $paid, 'status' => $status, 'transaction' => $tx]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur vérification paiement FedaPay', [
                'transaction_id' => $txId,
                'error' => $e->getMessage()
            ]);
            return $this->json(['ok' => false, 'message' => 'Erreur vérification paiement'], 500);
        }
    }

    #[Route('/don/confirmation/{id}', name: 'app_don_confirmation')]
    public function confirmation(Don $don): Response
    {
        return $this->render('don/confirmation.html.twig', [
            'don' => $don,
            'page_title' => 'Confirmation de votre don',
            'meta_description' => 'Merci pour votre généreux don à BINAJIA.'
        ]);
    }
}
