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
        $roleBadge = 'MEMBRE'; // Valeur par défaut
        $roleTitle = 'MEMBER\nBINAJIA'; // Valeur par défaut
        
        // Vérifier et préparer l'avatar
        $avatarFullPath = $this->prepareAvatar($avatarPath);

        $this->pdfGenerator->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => $avatarFullPath,
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
            $receiptNumber = sprintf('R-%s-%d', date('YmdHis'), (int)$payment->getId());
            $receiptPdfPath = $receiptsDir . sprintf('/receipt_%d.pdf', (int)$payment->getId());
            $this->pdfGenerator->generatePdf(
                'invoice/receipt.html.twig',
                [
                    'user' => $user,
                    'payment' => $payment,
                    'receiptNumber' => $receiptNumber,
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

    /**
     * Prépare et vérifie l'avatar pour la génération PDF avec DomPDF
     */
    private function prepareAvatar(string $avatarPath): ?string
    {
        if (empty($avatarPath)) {
            return $this->createDefaultAvatar();
        }

        // Construire le chemin absolu du fichier
        if (str_starts_with($avatarPath, '/')) {
            $fullPath = $this->projectDir . '/public' . $avatarPath;
        } else {
            $fullPath = $this->projectDir . '/public/' . ltrim($avatarPath, '/');
        }

        // Vérifier si le fichier existe et est lisible
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            error_log("Avatar file not found or not readable: " . $fullPath);
            return $this->createDefaultAvatar();
        }

        // Pour DomPDF, convertir en data URI si c'est une image
        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo !== false) {
            $imageData = file_get_contents($fullPath);
            $base64 = base64_encode($imageData);
            $mimeType = $imageInfo['mime'];
            return "data:$mimeType;base64,$base64";
        }

        // Si ce n'est pas une image valide, utiliser l'avatar par défaut
        return $this->createDefaultAvatar();
    }

    /**
     * Crée un avatar par défaut pour DomPDF en data URI
     */
    private function createDefaultAvatar(): string
    {
        // Créer une image PNG simple en data URI pour DomPDF
        $defaultImage = <<<SVG
<svg width="80" height="95" xmlns="http://www.w3.org/2000/svg">
    <rect width="80" height="95" fill="#f0f0f0" stroke="#ccc" stroke-width="1"/>
    <circle cx="40" cy="30" r="15" fill="#0a4b1e"/>
    <ellipse cx="40" cy="70" rx="20" ry="12" fill="#0a4b1e"/>
    <text x="40" y="85" text-anchor="middle" font-family="Arial" font-size="8" fill="#666">PHOTO</text>
</svg>
SVG;

        // Convertir le SVG en data URI
        $base64 = base64_encode($defaultImage);
        return "data:image/svg+xml;base64,$base64";
    }
}
