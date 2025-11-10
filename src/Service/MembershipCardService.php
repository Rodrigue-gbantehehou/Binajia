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
    ) {}

    /**
     * Génère la carte PDF et éventuellement le reçu, 
     * enregistre les entités et renvoie les chemins sécurisés.
     *
     * @return array
     */
    public function generateAndPersist(User $user, ?Payments $payment, string $avatarPath, string $memberId, string $plan): array
    {
        // 1️⃣ Vérifier si une carte existe déjà pour cet utilisateur
        $existingCard = $this->em->getRepository(MembershipCards::class)->findOneBy(['user' => $user]);

        if ($existingCard) {
            // Carte existante - la mettre à jour
            $card = $existingCard;
            if (!$card->getCardnumberC()) {
                $card->setCardnumberC($memberId);
            }
            $card->setStatus(true);
            $card->setPhoto($this->getRelativePath($avatarPath));
        } else {
            // Création d'une nouvelle carte
            $card = new MembershipCards();
            $card->setCardnumberC($memberId);
            $card->setIssuedate(new \DateTime());
            $card->setExpiryDate((new \DateTime())->modify('+1 year'));
            $card->setStatus(true);
            $card->setPhoto($this->getRelativePath($avatarPath));
            $card->setUser($user);
            if ($plan === 'premium') {
                $card->setRoleoncard('Membre Prestige');
            } else if ($plan === 'standard') {
                $card->setRoleoncard('Membre VIP');
            } elseif ($plan === 'basic') {
                $card->setRoleoncard('Membre Premium');
            }
            $this->em->persist($card);
        }

        $this->em->flush();

        // 2️⃣ Dossiers sécurisés
        $cardsDir = $this->uploadDir . '/cards';
        $receiptsDir = $this->uploadDir . '/receipts';

        if (!is_dir($cardsDir)) {
            @mkdir($cardsDir, 0775, true);
        }
        if (!is_dir($receiptsDir)) {
            @mkdir($receiptsDir, 0775, true);
        }

        // 3️⃣ Génération du PDF de la carte
        $stamp = date('YmdHis');
        $cardFilename = sprintf('card_%d_%s.pdf', (int)$user->getId(), $stamp);

        $issuedAt = new \DateTime();
        $expiryAt = (clone $issuedAt)->modify('+1 year');
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');
        $roleBadge = $card->getRoleoncard();
        $roleTitle = 'MEMBER\nBINAJIA';

        // 4️⃣ Utiliser l'avatar depuis la carte (déjà récupérée ou créée)
        $avatarFullPath = $this->prepareAvatar($card->getPhoto() ?: '');

        // 5️⃣ QR Code
        $qrData = sprintf(
            "BINAJIA Member\nID: %s\nName: %s\nPhone: %s\nExpiry: %s",
            $memberId,
            $name,
            $phone,
            $expiryAt->format('d/m/Y')
        );
        $qrCode = $this->qrCodeService->generate($qrData);

        // 6️⃣ PDF Carte - Capture the return value from generatePdf method

        if ($plan === 'premium') {
            $cardPdfPath = $this->pdfGenerator->generatePdf(
                'membership/cardprestige.html.twig',
                [
                    'avatar' => $avatarFullPath,
                    'name' => $name,
                    'phone' => $phone,
                    'nationality' => $nationality,
                    'roleBadge' => 'Membre Binajia',
                    'roleTitle' => $roleTitle,
                    'memberId' => $memberId,
                    'expiry' => $expiryAt->format('d/m/Y'),
                    'joinDate' => $issuedAt->format('d/m/Y'),
                    'qrCode' => $qrCode,
                ],
                $cardFilename,
                'A4',
                'portrait',
                $cardsDir
            );
        } else if ($plan === 'standard') {
            $cardPdfPath = $this->pdfGenerator->generatePdf(
                'membership/cardvip.html.twig',
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
                $cardFilename,
                'A4',
                'portrait',
                $cardsDir
            );
        } else if ($plan === 'basic') {
            $cardPdfPath = $this->pdfGenerator->generatePdf(
                'membership/cardpremium.html.twig',
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
                $cardFilename,
                'A4',
                'portrait',
                $cardsDir
            );
        }

        // 7️⃣ Reçu PDF (optionnel)
        $receiptPdfPath = null;
        if ($payment) {
            $receiptNumber = sprintf('R-%s-%d', date('YmdHis'), (int)$payment->getId());
            $receiptFilename = sprintf('receipt_%d.pdf', (int)$payment->getId());

            // Capture the return value from generatePdf method for receipt
            $receiptPdfPath = $this->pdfGenerator->generatePdf(
                'invoice/receipt.html.twig',
                [
                    'user' => $user,
                    'payment' => $payment,
                    'receiptNumber' => $receiptNumber,
                    'issuedAt' => new \DateTime(),
                ],
                $receiptFilename,
                'A4',
                'portrait',
                $receiptsDir
            );

            $receipt = new Receipts();
            $receipt->setReceiptNumber($receiptNumber);
            $receipt->setIssuedDate(new \DateTime());
            $receipt->setPayment($payment);
            $receipt->setPdfurl($this->getRelativePath($receiptPdfPath));

            $this->em->persist($receipt);
            $this->em->flush();
        }

        // 8️⃣ Mise à jour de la carte avec le chemin PDF (chemin relatif)
        $card->setPdfurl($this->getRelativePath($cardPdfPath));
        $this->em->flush();

        return [
            'cardPdfPath' => $cardPdfPath, // Chemin absolu pour usage immédiat
            'cardPdfUrl' => $this->getRelativePath($cardPdfPath), // Chemin relatif pour la BD
            'receiptPdfPath' => $receiptPdfPath, // Chemin absolu pour usage immédiat
            'receiptPdfUrl' => $receiptPdfPath ? $this->getRelativePath($receiptPdfPath) : null, // Chemin relatif pour la BD
        ];
    }

    public function prepareAvatar(string $avatarPath): ?string
    {
        if (empty($avatarPath)) {
            return $this->createDefaultAvatar();
        }

        // Utiliser le chemin relatif tel quel depuis la base de données
        $fullPath = $this->uploadDir . '/' . ltrim($avatarPath, '/');

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

    public function createDefaultAvatar(): string
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

    /**
     * Génère une carte PDF pour un membre existant en utilisant son vrai avatar enregistré
     */
    public function generateCardWithExistingAvatar(MembershipCards $card, ?Payments $payment = null): array
    {
        $user = $card->getUser();
        $memberId = $card->getCardnumberC();

        // Récupérer le vrai chemin de l'avatar depuis la carte
        $avatarPath = $card->getPhoto();

        // Dossiers sécurisés
        $cardsDir = $this->uploadDir . '/cards';
        $receiptsDir = $this->uploadDir . '/receipts';

        if (!is_dir($cardsDir)) {
            @mkdir($cardsDir, 0775, true);
        }
        if (!is_dir($receiptsDir)) {
            @mkdir($receiptsDir, 0775, true);
        }

        // Génération du PDF de la carte
        $stamp = date('YmdHis');
        $cardFilename = sprintf('card_%d_%s.pdf', (int)$user->getId(), $stamp);

        $issuedAt = $card->getIssuedate() ?? new \DateTime();
        $expiryAt = $card->getExpiryDate() ?? (new \DateTime())->modify('+1 year');
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');
        $roleBadge = 'MEMBRE';
        $roleTitle = 'MEMBER\nBINAJIA';

        // Utiliser le vrai avatar enregistré du membre
        $avatarFullPath = $this->prepareAvatar($avatarPath);

        // QR Code
        $qrData = sprintf(
            "BINAJIA Member\nID: %s\nName: %s\nPhone: %s\nExpiry: %s",
            $memberId,
            $name,
            $phone,
            $expiryAt->format('d/m/Y')
        );
        $qrCode = $this->qrCodeService->generate($qrData);

        // PDF Carte - Capture the return value from generatePdf method
        $cardPdfPath = $this->pdfGenerator->generatePdf(
            'membership/card.html.twig',
            [
                'avatar' => $avatarFullPath, // Utilise le vrai avatar préparé
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
            $cardFilename,
            'A4',
            'portrait',
            $cardsDir
        );

        return [
            'cardPdfPath' => $cardPdfPath,
            'cardPdfUrl' => $this->getRelativePath($cardPdfPath),
        ];
    }

    /**
     * Convertit un chemin absolu en chemin relatif depuis le dossier d'upload
     */
    private function getRelativePath(string $absolutePath): string
    {
        // Trouver la partie du chemin qui vient après le dossier d'upload
        $uploadPos = strpos($absolutePath, $this->uploadDir);
        if ($uploadPos !== false) {
            $relativePath = substr($absolutePath, $uploadPos + strlen($this->uploadDir));
            // Remplacer les backslashes par des slashes pour la compatibilité web
            return str_replace('\\', '/', $relativePath);
        }

        // Si on ne trouve pas le dossier d'upload, retourner le nom du fichier seulement
        return '/' . basename($absolutePath);
    }
}
