<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendMembershipEmail(string $to, string $subject, string $htmlContent, ?string $cardPdfPath = null, ?string $receiptPdfPath = null, ?string $receiptFileName = null): void
    {
        $fromAddress = $_ENV['MAIL_FROM_ADDRESS']
            ?? $_SERVER['MAIL_FROM_ADDRESS']
            ?? $_ENV['MAIL_FROM_ADRESS']
            ?? $_SERVER['MAIL_FROM_ADRESS']
            ?? 'no-reply@binajia.org';
        $fromName = $_ENV['MAIL_FROM_NAME']
            ?? $_SERVER['MAIL_FROM_NAME']
            ?? 'BINAJIA';

        $email = (new Email())
            ->from(sprintf('%s <%s>', $fromName, $fromAddress))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        // Ajouter la carte de membre en pièce jointe
        if ($cardPdfPath && file_exists($cardPdfPath)) {
            $email->attachFromPath($cardPdfPath, 'carte_membre_' . basename($cardPdfPath), 'application/pdf');
        }

        // Ajouter le reçu en pièce jointe
        if ($receiptPdfPath && file_exists($receiptPdfPath)) {
            $receiptName = $receiptFileName ?? ('reçu_' . basename($receiptPdfPath));
            $email->attachFromPath($receiptPdfPath, $receiptName, 'application/pdf');
        }

        $this->mailer->send($email);
    }
    public function sendDonationReceipt(string $to, string $subject, string $htmlContent, ?string $receiptPdfPath = null, ?string $receiptFileName = null): void
    {
        $fromAddress = $_ENV['MAIL_FROM_ADDRESS']
            ?? $_SERVER['MAIL_FROM_ADDRESS']
            ?? $_ENV['MAIL_FROM_ADRESS']
            ?? $_SERVER['MAIL_FROM_ADRESS']
            ?? 'no-reply@binajia.org';
        $fromName = $_ENV['MAIL_FROM_NAME']
            ?? $_SERVER['MAIL_FROM_NAME']
            ?? 'BINAJIA';

        $email = (new Email())
            ->from(sprintf('%s <%s>', $fromName, $fromAddress))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        // Ajouter le reçu en pièce jointe
        if ($receiptPdfPath && file_exists($receiptPdfPath)) {
            $receiptName = $receiptFileName ?? ('reçu_' . basename($receiptPdfPath));
            $email->attachFromPath($receiptPdfPath, $receiptName, 'application/pdf');
        }

        $this->mailer->send($email);
    }
}
