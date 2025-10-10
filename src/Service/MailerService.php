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
        $email = (new Email())
            ->from('no-reply@binajia.org')
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        if ($attachmentPath && file_exists($attachmentPath)) {
            $email->attachFromPath($attachmentPath, $attachmentName ?? basename($attachmentPath), 'application/pdf');
        }

        $this->mailer->send($email);
    }
}
