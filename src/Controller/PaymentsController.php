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
            return new JsonResponse(['ok' => false, 'message' => 'Missing secret key'], 500);
        }

        $apiUrl = sprintf('https://api.fedapay.com/v1/transactions/%s', urlencode($txId));
        try {
            $resp = $httpClient->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret,
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
            ]);
            $http = $resp->getStatusCode();
            if ($http < 200 || $http >= 300) {
                return new JsonResponse(['ok' => false, 'message' => 'API error', 'http' => $http], 200);
            }
            $json = $resp->toArray(false);
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'message' => 'HTTP client error', 'error' => $e->getMessage()], 200);
        }

        // Expected shape: { "transaction": { "id": ..., "status": "approved", "amount": ... } }
        $tx = $json['transaction'] ?? null;
        $status = $tx['status'] ?? null;
        $amount = $tx['amount'] ?? null;

        return new JsonResponse([
            'ok' => true,
            'status' => $status,
            'amount' => $amount,
            'raw' => $tx,
        ]);
    }
}
