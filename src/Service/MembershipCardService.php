<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Payments;
use App\Entity\Receipts;
use App\Entity\MembershipCards;
use Doctrine\ORM\EntityManagerInterface;

class MembershipCardService
{
    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly EntityManagerInterface $em,
        private readonly string $uploadDir, // ⚡ on injecte var/uploads
        private readonly QrCodeService $qrCodeService,
    ) {
    }

    /**
     * Génère la carte PDF et éventuellement le reçu, 
     * enregistre les entités et renvoie les chemins sécurisés.
     *
     * @return array{cardPdfPath: string, receiptPdfPath: ?string}
     */
    public function generateAndPersist(User $user, ?Payments $payment, string $avatarPath, string $memberId): array
    {
        // 1️⃣ Création de l'entité carte
        $card = new MembershipCards();
        $card->setCardnumberC($memberId);
        $card->setIssuedate(new \DateTime());
        $card->setExpiryDate((new \DateTime())->modify('+1 year'));
        $card->setStatus(true);
        $card->setPhoto($avatarPath);
        $card->setUser($user);

        $this->em->persist($card);
        $this->em->flush();

        // 2️⃣ Dossiers sécurisés
        $cardsDir = $this->uploadDir . '/cards';
        $receiptsDir = $this->uploadDir . '/receipts';

        if (!is_dir($cardsDir)) { @mkdir($cardsDir, 0775, true); }
        if (!is_dir($receiptsDir)) { @mkdir($receiptsDir, 0775, true); }

        // 3️⃣ Génération du PDF de la carte
        $stamp = date('YmdHis');
        $cardFilename = sprintf('card_%d_%s.pdf', (int)$user->getId(), $stamp);
        $cardPdfPath = $cardsDir . '/' . $cardFilename;

        $issuedAt = new \DateTime();
        $expiryAt = (clone $issuedAt)->modify('+1 year');
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');
        $roleBadge = 'MEMBRE';
        $roleTitle = 'MEMBER\nBINAJIA';

        // 4️⃣ Avatar sécurisé
        $avatarFullPath = $this->prepareAvatar($avatarPath);

        // 5️⃣ QR Code
        $qrData = sprintf(
            "BINAJIA Member\nID: %s\nName: %s\nPhone: %s\nExpiry: %s",
            $memberId,
            $name,
            $phone,
            $expiryAt->format('d/m/Y')
        );
        $qrCode = $this->qrCodeService->generate($qrData);

        // 6️⃣ PDF Carte
        $this->pdfGenerator->generatePdf(
            'membership/card.html.twig',
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
                'qrCode' => $qrCode,
            ],
            $cardPdfPath
        );

        // 7️⃣ Reçu PDF (optionnel)
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
                $receiptPdfPath
            );

            $receipt = new Receipts();
            $receipt->setReceiptNumber($receiptNumber);
            $receipt->setIssuedDate(new \DateTime());
            $receipt->setPayment($payment);
            $receipt->setPdfurl($receiptPdfPath);

            $this->em->persist($receipt);
            $this->em->flush();
        }

        // 8️⃣ Mise à jour de la carte avec le chemin PDF
        $card->setPdfurl($cardPdfPath);
        $this->em->flush();

        return [
            'cardPdfPath' => $cardPdfPath,
            'receiptPdfPath' => $receiptPdfPath,
        ];
    }

    private function prepareAvatar(string $avatarPath): ?string
    {
        if (empty($avatarPath)) {
            return $this->createDefaultAvatar();
        }

        $fullPath = $this->uploadDir . '/avatars/' . basename($avatarPath);

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return $this->createDefaultAvatar();
        }

        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo !== false) {
            $imageData = file_get_contents($fullPath);
            $base64 = base64_encode($imageData);
            $mimeType = $imageInfo['mime'];
            return "data:$mimeType;base64,$base64";
        }

        return $this->createDefaultAvatar();
    }

    private function createDefaultAvatar(): string
    {
        $defaultImage = <<<SVG
<svg width="80" height="95" xmlns="http://www.w3.org/2000/svg">
    <rect width="80" height="95" fill="#f0f0f0" stroke="#ccc" stroke-width="1"/>
    <circle cx="40" cy="30" r="15" fill="#0a4b1e"/>
    <ellipse cx="40" cy="70" rx="20" ry="12" fill="#0a4b1e"/>
    <text x="40" y="85" text-anchor="middle" font-family="Arial" font-size="8" fill="#666">PHOTO</text>
</svg>
SVG;

        $base64 = base64_encode($defaultImage);
        return "data:image/svg+xml;base64,$base64";
    }
}
