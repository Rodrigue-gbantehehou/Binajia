<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymentsController extends AbstractController
{
    #[Route('/payment/verify', name: 'payment_verify', methods: ['GET'])]
    public function verify(Request $request, HttpClientInterface $httpClient): Response
    {
        $txId = $request->query->get('tx');
        if (!$txId) {
            return new JsonResponse(['ok' => false, 'message' => 'Missing tx'], 400);
        }

        $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? $_SERVER['FEDAPAY_SECRET_KEY'] ?? null;
        if (!$secret) {
            \error_log("❌ FEDAPAY_SECRET_KEY manquante");
            return new JsonResponse(['ok' => false, 'message' => 'Missing secret key'], 500);
        }

        $apiUrl = sprintf('https://api.fedapay.com/v1/transactions/%s', urlencode($txId));
        \error_log("📡 Vérification FedaPay pour transaction: $txId");
        \error_log("📡 URL API: $apiUrl");
        
        try {
            $resp = $httpClient->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret,
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
            ]);
            $http = $resp->getStatusCode();
            \error_log("📡 Réponse HTTP FedaPay: $http");
            
            if ($http < 200 || $http >= 300) {
                \error_log("❌ Erreur HTTP FedaPay: $http");
                return new JsonResponse(['ok' => false, 'message' => 'API error', 'http' => $http], 200);
            }
            $json = $resp->toArray(false);
            \error_log("✅ Réponse FedaPay: " . json_encode($json));
        } catch (\Throwable $e) {
            \error_log("❌ Erreur HTTP client: " . $e->getMessage());
            return new JsonResponse(['ok' => false, 'message' => 'HTTP client error', 'error' => $e->getMessage()], 200);
        }

        // Expected shape: { "transaction": { "id": ..., "status": "approved", "amount": ... } }
        $tx = $json['transaction'] ?? null;
        $status = $tx['status'] ?? null;
        $amount = $tx['amount'] ?? null;

        \error_log("✅ Transaction trouvée - Status: $status, Amount: $amount");

        return new JsonResponse([
            'ok' => true,
            'status' => $status,
            'amount' => $amount,
            'raw' => $tx,
        ]);
    }
}
