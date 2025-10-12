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

    public function sendMembershipEmail(string $to, string $subject, string $htmlContent, ?string $attachmentPath = null, ?string $attachmentName = null): void
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

        if ($attachmentPath && file_exists($attachmentPath)) {
            $email->attachFromPath($attachmentPath, $attachmentName ?? basename($attachmentPath), 'application/pdf');
        }

        $this->mailer->send($email);
    }
}
