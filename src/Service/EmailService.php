<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Psr\Log\LoggerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'contact@binajia.org',
        private string $fromName = 'BINAJIA'
    ) {}

    /**
     * Envoie un email de bienvenue avec les identifiants de connexion
     */
    public function sendWelcomeEmail(string $toEmail, string $firstName, string $lastName, string $tempPassword): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject('Bienvenue chez BINAJIA - Vos identifiants de connexion')
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $toEmail,
                    'tempPassword' => $tempPassword,
                    'loginUrl' => 'https://binajia.org/login'
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
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject('Votre carte de membre BINAJIA est prête !')
                ->htmlTemplate('emails/card_created.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'cardNumber' => $cardNumber,
                    'pdfUrl' => $pdfUrl,
                    'downloadUrl' => 'https://binajia.org' . $pdfUrl
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
                ->from($this->fromEmail)
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
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject('Paiement confirmé - Bienvenue dans la communauté BINAJIA !')
                ->htmlTemplate('emails/payment_confirmation.html.twig')
                ->context([
                    'firstName' => $firstName,
                    'reference' => $reference,
                    'amount' => $amount,
                    'currency' => 'FCFA',
                    'dashboardUrl' => 'https://binajia.org/dashboard'
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
}
