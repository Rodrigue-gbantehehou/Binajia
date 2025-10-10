<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Payments;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\PdfGeneratorService;
use App\Service\MailerService;

class RegistrationController extends AbstractController
{
    #[Route('/devenir-membre/submit', name: 'app_membership_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        HttpClientInterface $httpClient,
        Environment $twig,
        Security $security,
        LoggerInterface $logger,
        PdfGeneratorService $pdfGenerator,
        MailerService $mailerService
    ): Response {
        $firstName = trim((string)$request->request->get('firstName'));
        $lastName  = trim((string)$request->request->get('lastName'));
        $email     = trim((string)$request->request->get('email'));
        $phone     = trim((string)$request->request->get('phone'));
        $country   = trim((string)$request->request->get('country')) ?: null;
        $birthDate = trim((string)$request->request->get('birthDate')) ?: null;
        $plan      = trim((string)$request->request->get('plan')) ?: 'standard';
        $photoData = (string)$request->request->get('photoData'); // base64 data URL (required)

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
            return $this->json(['ok' => false, 'message' => 'Champs requis manquants'], 400);
        }

        // Basic server-side phone normalization (towards E.164)
        $rawPhone = preg_replace('/\s+/', '', $phone);
        $rawPhone = preg_replace('/[^\d\+]/', '', $rawPhone);
        if ($rawPhone && $rawPhone[0] !== '+') {
            // if country code missing, reject (frontend should prefix). Safer than guessing.
            return $this->json(['ok' => false, 'message' => 'Veuillez inclure l\'indicatif pays (ex: +22912345678).'], 400);
        }
        // minimal length check (country code + local), usually >= 8
        if (strlen(preg_replace('/\D/', '', $rawPhone)) < 8) {
            return $this->json(['ok' => false, 'message' => 'Numéro de téléphone invalide.'], 400);
        }

        // Normalize country code to localized name if symfony/intl is available
        if ($country && strlen($country) === 2 && class_exists(\Symfony\Component\Intl\Countries::class)) {
            $name = \Symfony\Component\Intl\Countries::getName(strtoupper($country), 'fr');
            if ($name) {
                $country = $name;
            }
        }

        // Prevent duplicate email: reuse existing user if present
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $email]);
        $isNewUser = false;
        $plainPassword = null;
        if (!$user) {
            // Create user
            $user = new User();
            $user->setEmail($email);
            $isNewUser = true;
        }
        // Update/assign common fields
        $user->setFirstname($firstName);
        $user->setLastname($lastName);
        $user->setPhone($rawPhone);
        $user->setCountry($country);
        if (!$user->getCreatedAt()) { $user->setCreatedAt(new \DateTime()); }

        if ($isNewUser) {
            // Generate a random password and hash it
            $plainPassword = bin2hex(random_bytes(6));
            $hashed = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
            $em->persist($user);
        }

        $em->flush(); // to get an ID or save updates

        // Build a member ID like BjNg-YYYY-000{id}
        $memberId = sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId());

        // Photo is required: decode, crop (3:4 portrait), and save JPEG for card
        $avatarPath = null;
        if ($photoData && str_starts_with($photoData, 'data:image')) {
            $data = explode(',', $photoData, 2);
            if (count($data) === 2) {
                $bin = base64_decode($data[1]);
                if ($bin !== false) {
                    $src = @imagecreatefromstring($bin);
                    if ($src !== false) {
                        $srcW = imagesx($src); $srcH = imagesy($src);
                        // Target aspect 3:4 (width:height)
                        $targetRatio = 3/4;
                        $srcRatio = $srcW / max(1, $srcH);
                        if ($srcRatio > $targetRatio) {
                            // too wide: crop width
                            $newW = (int) round($srcH * $targetRatio);
                            $newH = $srcH;
                            $srcX = (int) max(0, floor(($srcW - $newW)/2));
                            $srcY = 0;
                        } else {
                            // too tall: crop height
                            $newW = $srcW;
                            $newH = (int) round($srcW / $targetRatio);
                            $srcX = 0;
                            $srcY = (int) max(0, floor(($srcH - $newH)/2));
                        }
                        $crop = imagecreatetruecolor($newW, $newH);
                        imagecopy($crop, $src, 0, 0, $srcX, $srcY, $newW, $newH);
                        // Resize to consistent output (e.g., 300x400)
                        $outW = 300; $outH = 400;
                        $dst = imagecreatetruecolor($outW, $outH);
                        imagecopyresampled($dst, $crop, 0, 0, 0, 0, $outW, $outH, $newW, $newH);
                        imagedestroy($crop);

                        $dir = $this->getParameter('kernel.project_dir') . '/public/media/avatars';
                        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                        $avatarPath = sprintf('/media/avatars/%d.jpg', $user->getId());
                        $fsPath = $this->getParameter('kernel.project_dir') . $avatarPath;
                        @imagejpeg($dst, $fsPath, 90);
                        imagedestroy($dst);
                        imagedestroy($src);
                    }
                }
            }
        }
        if ($avatarPath === null) {
            return $this->json(['ok' => false, 'message' => 'La photo est obligatoire pour générer la carte.'], 400);
        }

        // Redirect URL to view the card
        $url = $this->generateUrl('app_membership_card_generated', [
            'id' => $user->getId(),
        ]);

        // If payment details were provided by the frontend, store them
        $txId = $request->request->get('transactionId');
        $txStatus = $request->request->get('transactionStatus');
        $amount = $request->request->get('amount');
        if ($txId) {
            $payment = new Payments();
            if ($amount !== null && $amount !== '') {
                // store as decimal string (entity expects string)
                $payment->setAmount((string) number_format((float) $amount, 2, '.', ''));
            } else {
                $payment->setAmount('0.00');
            }
            $payment->setPaymentMethod('FedaPay');
            $payment->setPaymentdate(new \DateTime());
            $payment->setStatus($txStatus ?: 'pending');
            $payment->setReference((string) $txId);
            $payment->setUser($user);
            $em->persist($payment);
            $em->flush();

            // Server-side verification with FedaPay API if secret key available (non-fatal)
            $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? $_SERVER['FEDAPAY_SECRET_KEY'] ?? null;
            $verifiedStatus = null;
            if ($secret) {
                try {
                    $apiUrl = sprintf('https://api.fedapay.com/v1/transactions/%s', urlencode((string)$txId));
                    $resp = $httpClient->request('GET', $apiUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $secret,
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 15,
                    ]);
                    if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                        $json = $resp->toArray(false);
                        $tx = $json['transaction'] ?? null;
                        if ($tx && isset($tx['status'])) {
                            $verifiedStatus = (string)$tx['status'];
                            $payment->setStatus($verifiedStatus);
                            if (isset($tx['amount'])) {
                                $payment->setAmount((string) number_format((float)$tx['amount'], 2, '.', ''));
                            }
                            $em->flush();
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore verification failures
                }
            }

            // If payment approved, send credentials + card link by email
            $finalStatus = $verifiedStatus ?: $txStatus;
            if (is_string($finalStatus) && in_array(strtolower($finalStatus), ['approved','succeeded','success','paid'])) {
                try {
                    // Génération de la carte membre PDF
                    $cardsDir = $this->getParameter('kernel.project_dir') . '/public/media/cards';
                    if (!is_dir($cardsDir)) { @mkdir($cardsDir, 0775, true); }
                    $stamp = date('YmdHis');
                    $cardFilename = sprintf('card_%d_%s.pdf', $user->getId(), $stamp);
                    $cardPdfPath = $cardsDir . '/' . $cardFilename;
                    $cardPdfUrl = '/media/cards/' . $cardFilename;

                    // Définir la variable bgVerso (exemple de chemin d'image ou valeur par défaut)
                    $bgVerso = '/images/card_bg_verso.jpg';

                    $pdfGenerator->generatePdf(
                        'membership/card_pdf_recto_verso.html.twig',
                        ['bgVerso' => $bgVerso], // Ajoutez les autres variables nécessaires
                        $cardPdfPath,
                        'A6',
                        'landscape'
                    );

                    // Génération du reçu PDF
                    $receiptsDir = $this->getParameter('kernel.project_dir') . '/public/media/receipts';
                    if (!is_dir($receiptsDir)) { @mkdir($receiptsDir, 0775, true); }
                    $receiptPdfPath = $receiptsDir . sprintf('/receipt_%d.pdf', $payment->getId());

                    $pdfGenerator->generatePdf(
                        'invoice/receipt.html.twig',
                        ['issuedAt' => new \DateTime()], // Ajoutez les autres variables nécessaires
                        $receiptPdfPath,
                        'A4',
                        'portrait'
                    );

                    // Envoi de l'e-mail avec pièce jointe
                    $loginUrl = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $cardUrlAbs = $this->generateUrl('app_membership_card_generated', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $amountTxt = $payment->getAmount();

                    $htmlContent = $twig->render('emails/membership.html.twig', [
                        'user' => $user,
                        'loginUrl' => $loginUrl,
                        'cardUrlAbs' => $cardUrlAbs,
                        'txId' => $txId,
                        'amountTxt' => $amountTxt,
                        'plainPassword' => $plainPassword,
                        'isNewUser' => $isNewUser,
                    ]);

                    $mailerService->sendMembershipEmail(
                        $user->getEmail(),
                        'Votre adhésion Binajia — Identifiants et carte membre',
                        $htmlContent,
                        $receiptPdfPath,
                        'recu_binajia.pdf'
                    );

                    $security->login($user);
                } catch (\Throwable $e) {
                    // Ignore mail errors to not bloquer le flow
                }
            }
        }

        return $this->json([
            'ok' => true,
            'cardUrl' => $url,
            'avatar' => $avatarPath,
            'redirect' => $this->generateUrl('app_user_dashboard'),
        ]);
    }

    #[Route('/membership/card/generated/{id}', name: 'app_membership_card_generated', methods: ['GET'])]
    public function cardGenerated(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }
        $memberId = sprintf('BjNg-%s-%03d', (new \DateTime())->format('Y'), $user->getId());

        // Optionally pass avatar path via query if saved
        $avatar = $request->query->get('avatar');
        $plan = $request->query->get('plan', 'standard');

        // Display country as localized name if possible
        $displayCountry = $user->getCountry();
        if ($displayCountry && strlen($displayCountry) === 2 && class_exists(\Symfony\Component\Intl\Countries::class)) {
            $name = \Symfony\Component\Intl\Countries::getName(strtoupper($displayCountry), 'fr');
            if ($name) { $displayCountry = $name; }
        }

        return $this->render('membership/card_generated.html.twig', [
            'user' => $user,
            'memberId' => $memberId,
            'plan' => $plan,
            'avatar' => $avatar,
            'displayCountry' => $displayCountry,
        ]);
    }
}
