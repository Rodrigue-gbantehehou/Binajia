<?php

namespace App\Service;

use App\Entity\MembershipCards;
use App\Entity\User;
use App\Entity\Payments;
use App\Entity\Receipts;
use Doctrine\ORM\EntityManagerInterface;

class MembershipCardService
{
    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly EntityManagerInterface $em,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Generate the membership card PDF and optionally the receipt,
     * persist MembershipCards entity, and return public URLs/paths.
     *
     * @return array{cardPdfUrl: string, receiptPdfPath: ?string}
     */
    public function generateAndPersist(User $user, ?Payments $payment, string $avatarPath, string $memberId): array
    {
        // 1) Persist the MembershipCards entity first (without pdf URL)
        $card = new MembershipCards();
        $card->setCardnumberC($memberId);
        $card->setIssuedate(new \DateTime());
        $card->setExpiryDate((new \DateTime())->modify('+1 year'));
        $card->setStatus(true);
        $card->setPhoto($avatarPath);
        $card->setUser($user);
        $this->em->persist($card);
        $this->em->flush(); // ensure it has an ID before generating files

        // 2) Ensure directories
        $cardsDir = $this->projectDir . '/public/media/cards';
        if (!is_dir($cardsDir)) { @mkdir($cardsDir, 0775, true); }
        $receiptsDir = $this->projectDir . '/public/media/receipts';
        if (!is_dir($receiptsDir)) { @mkdir($receiptsDir, 0775, true); }

        // 3) Generate card PDF (front and back styled)
        $stamp = date('YmdHis');
        $cardFilename = sprintf('card_%d_%s.pdf', (int)$user->getId(), $stamp);
        $cardPdfPath = $cardsDir . '/' . $cardFilename;
        $cardPdfUrl = '/media/cards/' . $cardFilename;

        // Compute dynamic fields
        $issuedAt = new \DateTime();
        $expiryAt = (clone $issuedAt)->modify('+1 year');
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');
        $roleBadge = $user->getRoleoncard() ?? 'MEMBRE';
        $roleTitle = $user->getRoleoncard() ? strtoupper($user->getRoleoncard()) . "\nBINAJIA" : 'MEMBER\nBINAJIA';

        $this->pdfGenerator->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => $avatarPath,
                'name' => $name,
                'phone' => $phone,
                'nationality' => $nationality,
                'roleBadge' => $roleBadge,
                'roleTitle' => $roleTitle,
                'memberId' => $memberId,
                'expiry' => $expiryAt->format('d/m/Y'),
                'joinDate' => $issuedAt->format('d/m/Y'),
            ],
            $cardPdfPath,
            'A6',
            'landscape'
        );

        // 4) Generate receipt PDF only if a payment is provided
        $receiptPdfPath = null;
        if ($payment) {
            $receiptPdfPath = $receiptsDir . sprintf('/receipt_%d.pdf', (int)$payment->getId());
            $this->pdfGenerator->generatePdf(
                'invoice/receipt.html.twig',
                [
                    'user' => $user,
                    'payment' => $payment,
                    'issuedAt' => new \DateTime(),
                ],
                $receiptPdfPath,
                'A4',
                'portrait'
            );

            // Persist a Receipts entity linked to the payment
            $receipt = new Receipts();
            $receipt->setReceiptNumber(sprintf('R-%s-%d', date('YmdHis'), (int)$payment->getId()));
            $receipt->setIssuedDate(new \DateTime());
            $receipt->setPayment($payment);
            $receipt->setPdfurl(str_replace($this->projectDir, '', $receiptPdfPath)); // public path
            $this->em->persist($receipt);
            $this->em->flush();
        }

        // 5) Update the card with the PDF URL and flush again
        $card->setPdfurl($cardPdfUrl);
        $this->em->flush();

        return [
            'cardPdfUrl' => $cardPdfUrl,
            'receiptPdfPath' => $receiptPdfPath,
        ];
    }
}
