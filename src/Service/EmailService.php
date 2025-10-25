<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Payments;
use Psr\Log\LoggerInterface;
use App\Entity\PasswordResetToken;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private ConfigurationService $config
    ) {}

    /**
     * Envoie un email de bienvenue avec les identifiants de connexion
     */
    public function sendWelcomeEmail(string $toEmail, string $firstName, string $lastName, string $tempPassword): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Bienvenue chez BINAJIA - Vos identifiants de connexion')
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'userEmail' => $toEmail,
                    'tempPassword' => $tempPassword,
                    'loginUrl' => $this->config->getLoginUrl()
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email de bienvenue envoyé', ['email' => $toEmail]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de bienvenue', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de confirmation de carte créée
     */
    public function sendCardCreatedEmail(string $toEmail, string $firstName, string $cardNumber, string $pdfUrl): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Votre carte de membre BINAJIA est prête !')
                ->htmlTemplate('emails/card_created.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'cardNumber' => $cardNumber,
                    'pdfUrl' => $pdfUrl,
                    'downloadUrl' => $this->config->getDownloadUrl($pdfUrl)
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email de carte créée envoyé', ['email' => $toEmail]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email carte créée', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de demande de paiement
     */
    public function sendPaymentRequestEmail(string $toEmail, string $firstName, string $paymentUrl, float $amount): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Finaliser votre adhésion BINAJIA - Paiement requis')
                ->htmlTemplate('emails/payment_request.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'amount' => $amount,
                    'paymentUrl' => $paymentUrl,
                    'currency' => 'FCFA'
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email de demande de paiement envoyé', ['email' => $toEmail]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email paiement', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de confirmation de paiement
     */
    public function sendPaymentConfirmationEmail(string $toEmail, string $firstName, string $reference, float $amount): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Paiement confirmé - Bienvenue dans la communauté BINAJIA !')
                ->htmlTemplate('emails/payment_confirmation.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'reference' => $reference,
                    'amount' => $amount,
                    'currency' => 'FCFA',
                    'dashboardUrl' => $this->config->getDashboardUrl()
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email de confirmation de paiement envoyé', ['email' => $toEmail]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email confirmation paiement', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(PasswordResetToken $token): bool
    {
        try {
            $user = $token->getUser();
            $resetUrl = $this->config->getBaseUrl() . '/reset-password/' . $token->getToken();

            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe - BINAJIA')
                ->htmlTemplate('emails/password_reset.html.twig')
                ->context([
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'resetUrl' => $resetUrl,
                    'expiresAt' => $token->getExpiresAt(),
                    'userEmail' => $user->getEmail()
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email de réinitialisation de mot de passe envoyé', ['email' => $user->getEmail()]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email réinitialisation mot de passe', [
                'email' => $token->getUser()->getEmail(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de confirmation de réservation avec le PDF en pièce jointe
     */
    public function sendReservationConfirmationEmail(string $toEmail, array $reservationData, string $pdfPath): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Confirmation de votre réservation - BINAJIA')
                ->htmlTemplate('emails/reservation_confirmation.html.twig')
                ->context([
                    'reservation' => (object) $reservationData,
                    'pdf_url' => $this->config->getBaseUrl() . $pdfPath,
                ])
                ->attachFromPath($pdfPath, 'confirmation-reservation-' . ($reservationData['id'] ?? 'new') . '.pdf', 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Email de confirmation de réservation envoyé', [
                'email' => $toEmail,
                'reservation_id' => $reservationData['id'] ?? null
            ]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de confirmation de réservation', [
                'email' => $toEmail,
                'reservation_id' => $reservationData['id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de confirmation de réservation avec le PDF de facture en pièce jointe
     */
    public function sendReservationConfirmation(Reservation $reservation, string $pdfPath): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($reservation->getEmail())
                ->subject('Facture Proforma - Votre réservation BINAJIA')
                ->htmlTemplate('emails/reservation_confirmation.html.twig')
                ->context([
                    'reservation' => $reservation,
                    'pdf_url' => $this->config->getBaseUrl() . $reservation->getFacturepdf(),
                ])
                ->attachFromPath($pdfPath, 'facture-proforma-' . $reservation->getId() . '.pdf', 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Email de confirmation de réservation envoyé', [
                'email' => $reservation->getEmail(),
                'reservation_id' => $reservation->getId()
            ]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de confirmation de réservation', [
                'email' => $reservation->getEmail(),
                'reservation_id' => $reservation->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email avec le reçu en pièce jointe
     */
    public function sendReceiptEmail(string $toEmail, string $firstName, Payments $payment, string $pdfPath): bool
    {
        try {
            $receiptNumber = sprintf('R-%s-%d', date('YmdHis'), (int)$payment->getId());

            $email = (new TemplatedEmail())
                ->from($this->config->getFromEmail())
                ->to($toEmail)
                ->subject('Votre reçu de paiement BINAJIA')
                ->htmlTemplate('emails/receipt.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'receiptNumber' => $receiptNumber,
                    'amount' => (float) $payment->getAmount(),
                    'currency' => 'FCFA',
                    'paymentDate' => $payment->getPaymentdate(),
                    'pdf_url' => $this->config->getBaseUrl() . $pdfPath,
                ])
                ->attachFromPath($pdfPath, 'reçu-' . $receiptNumber . '.pdf', 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Email de reçu envoyé', ['email' => $toEmail]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de reçu', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
