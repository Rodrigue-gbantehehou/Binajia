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

                // Rediriger vers le dashboard utilisateur
                if ($user) {
                    // Authentifier automatiquement l'utilisateur en créant une session
                    $session = $request->getSession();
                    $session->set('user_authenticated', true);
                    $session->set('user_id', $user->getId());

                    $this->addFlash('success', 'Paiement confirmé ! Votre carte de membre a été activée.');
                    $this->addFlash('info', 'Vous pouvez maintenant voir votre carte dans votre espace personnel.');

                    // Rediriger vers le dashboard avec l'utilisateur connecté
                    return $this->redirectToRoute('app_user_dashboard', [], Response::HTTP_SEE_OTHER);
                } else {
                    // Fallback si utilisateur non trouvé
                    $this->addFlash('success', 'Paiement confirmé ! Votre carte de membre a été activée.');
                    $this->addFlash('info', 'Vous allez recevoir vos documents par email.');
                    return $this->render('payment/success.html.twig', [
                        'message' => $result['message']
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
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }
}
