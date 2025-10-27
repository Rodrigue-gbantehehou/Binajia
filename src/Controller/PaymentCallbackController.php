<?php

namespace App\Controller;

use App\Entity\Payments;
use App\Service\CardPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentCallbackController extends AbstractController
{
    #[Route('/payment/callback', name: 'payment_callback', methods: ['GET', 'POST'])]
    public function callback(Request $request, CardPaymentService $paymentService, EntityManagerInterface $em): Response
    {
        $transactionId = $request->query->get('transaction_id') ?? $request->request->get('transaction_id');

        if (!$transactionId) {
            $this->addFlash('error', 'ID de transaction manquant.');
            return $this->redirectToRoute('home');
        }

        $result = $paymentService->verifyPaymentAndActivateCard($transactionId);

        if ($result['success']) {
            if ($result['status'] === 'completed') {
                // Récupérer l'utilisateur du paiement
                $reference = $result['reference'] ?? '';
                $payment = $em->getRepository(Payments::class)->findOneBy(['reference' => $reference]);
                $user = $payment ? $payment->getUser() : null;

                // 🔧 CORRECTION : Ne pas rediriger automatiquement, laisser le JavaScript gérer
                // Le JavaScript va appeler confirmMembership() qui redirigera vers la page de carte
                if ($user) {
                    // Authentifier automatiquement l'utilisateur en créant une session
                    $session = $request->getSession();
                    $session->set('user_authenticated', true);
                    $session->set('user_id', $user->getId());

                    // Retourner une réponse JSON pour le JavaScript au lieu de rediriger
                    return $this->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Paiement confirmé ! Votre carte de membre a été activée.',
                        'userId' => $user->getId(),
                        'redirectUrl' => $this->generateUrl('app_membership_card_generated', ['id' => $user->getId()])
                    ]);
                } else {
                    // Fallback si utilisateur non trouvé
                    return $this->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Paiement confirmé ! Votre carte de membre a été activée.',
                        'userId' => null,
                        'redirectUrl' => $this->generateUrl('payment_success')
                    ]);
                }
            } else {
                $this->addFlash('warning', 'Paiement en cours de traitement. Vous recevrez une confirmation par email.');
                return $this->render('payment/pending.html.twig', [
                    'status' => $result['status']
                ]);
            }
        } else {
            $this->addFlash('error', 'Erreur lors de la vérification du paiement: ' . $result['error']);
            return $this->render('payment/error.html.twig', [
                'error' => $result['error']
            ]);
        }
    }

    #[Route('/payment/cancel', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Paiement annulé. Vous pouvez réessayer à tout moment.');
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/payment/success', name: 'payment_success', methods: ['GET'])]
    public function success(Request $request, EntityManagerInterface $em): Response
    {
        $transactionId = $request->query->get('transaction_id');
        
        if ($transactionId) {
            // Récupérer l'utilisateur depuis la transaction
            $payment = $em->getRepository(Payments::class)->findOneBy(['reference' => $transactionId]);
            if ($payment && $payment->getUser()) {
                // Rediriger vers la page de confirmation de carte
                return $this->redirectToRoute('app_membership_card_generated', [
                    'id' => $payment->getUser()->getId()
                ]);
            }
        }
        
        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/auto-auth/{transactionId}', name: 'payment_auto_auth', methods: ['GET'])]
    public function autoAuth(string $transactionId, EntityManagerInterface $em, Request $request): Response
    {
        // Récupérer le paiement et l'utilisateur
        $payment = $em->getRepository(Payments::class)->findOneBy(['reference' => $transactionId]);
        
        if (!$payment || !$payment->getUser()) {
            return $this->json([
                'success' => false,
                'message' => 'Transaction ou utilisateur non trouvé'
            ], 404);
        }
        
        $user = $payment->getUser();
        
        // Créer une session d'authentification
        $session = $request->getSession();
        $session->set('user_authenticated', true);
        $session->set('user_id', $user->getId());
        $session->set('user_email', $user->getEmail());
        $session->set('user_name', $user->getFirstname() . ' ' . $user->getLastname());
        
        // Générer un token de session sécurisé
        $sessionToken = bin2hex(random_bytes(32));
        $session->set('session_token', $sessionToken);
        
        \error_log("✅ Auto-authentification réussie pour l'utilisateur: " . $user->getId());
        
        return $this->json([
            'success' => true,
            'message' => 'Authentification automatique réussie',
            'userId' => $user->getId(),
            'userEmail' => $user->getEmail(),
            'userName' => $user->getFirstname() . ' ' . $user->getLastname(),
            'sessionToken' => $sessionToken,
            'redirectUrl' => $this->generateUrl('app_membership_card_generated', ['id' => $user->getId()])
        ]);
    }
}
