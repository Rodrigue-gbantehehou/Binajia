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
        LoggerInterface $logger
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
                    $loginUrl = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $cardUrlAbs = $this->generateUrl('app_membership_card_generated', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $amountTxt = $payment->getAmount();

                    // 1) Generate Membership Card PDF (recto-verso)
                    $cardsDir = $this->getParameter('kernel.project_dir') . '/public/media/cards';
                    if (!is_dir($cardsDir)) { @mkdir($cardsDir, 0775, true); }
                    $stamp = date('YmdHis');
                    $cardFilename = sprintf('card_%d_%s.pdf', $user->getId(), $stamp);
                    $cardPdfPath = $cardsDir . '/' . $cardFilename;
                    $cardPdfUrl = '/media/cards/' . $cardFilename;
                    $publicDir = $this->getParameter('kernel.project_dir') . '/public';
                    $logoFs = $publicDir . '/media/logo.jpeg';
                    $logoForPdf = 'file://' . $logoFs;
                    $photoFs = 'file://' . ($this->getParameter('kernel.project_dir') . $avatarPath);
                    // Backgrounds for image-overlay approach (optional files you provide)
                    $bgRecto = 'file://' . ($publicDir . '/media/card_bg_recto.png');
                    $bgVerso = 'file://' . ($publicDir . '/media/card_bg_verso.png');
                    $cardHtml = $twig->render('membership/card_pdf_recto_verso.html.twig', [
                        'name' => $user->getFirstname() . ' ' . $user->getLastname(),
                        'phone' => $user->getPhone(),
                        'nationality' => $user->getCountry(),
                        'id' => $memberId,
                        'expiry' => (new \DateTime('+1 year'))->format('d/m/Y'),
                        'join_date' => ($user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y') : (new \DateTime())->format('d/m/Y')),
                        'role' => 'MEMBRE',
                        'roleTitle' => "MEMBER\nBINAJIA",
                        'logoUrl' => $logoForPdf,
                        'photoUrl' => $photoFs,
                        'bgRecto' => $bgRecto,
                        'bgVerso' => $bgVerso,
                    ]);
                    $options = new Options();
                    $options->set('defaultFont', 'DejaVu Sans');
                    $options->setIsRemoteEnabled(true);
                    // Restrict file access to public directory for safety
                    $options->setChroot($publicDir);
                    $dompdf = new Dompdf($options);
                    $dompdf->loadHtml($cardHtml, 'UTF-8');
                    $dompdf->setPaper('A6', 'landscape');
                    $dompdf->render();
                    $cardBytes = $dompdf->output();
                    $bytesWritten = @file_put_contents($cardPdfPath, $cardBytes);
                    $logger->info('Card PDF write attempt', ['path' => $cardPdfPath, 'bytes' => is_int($bytesWritten) ? $bytesWritten : null, 'ok' => is_int($bytesWritten) && $bytesWritten > 0]);
                    if (!file_exists($cardPdfPath)) {
                        $logger->error('Card PDF not found after write', ['path' => $cardPdfPath]);
                    }

                    // Save/Update MembershipCards row with PDF URL
                    $mc = new \App\Entity\MembershipCards();
                    $mc->setUser($user)
                        ->setCardnumberC($memberId)
                        ->setIssuedate(new \DateTime())
                        ->setExpiryDate(new \DateTime('+1 year'))
                        ->setStatus(true)
                        ->setPdfurl($cardPdfUrl);
                    $em->persist($mc);
                    $em->flush();
                    $logger->info('MembershipCards persisted', ['userId' => $user->getId(), 'pdfurl' => $cardPdfUrl]);

                    // 2) Generate Receipt PDF
                    $receiptsDir = $this->getParameter('kernel.project_dir') . '/public/media/receipts';
                    if (!is_dir($receiptsDir)) { @mkdir($receiptsDir, 0775, true); }
                    $receiptNumber = 'RC-' . date('Ymd') . '-' . str_pad((string)$payment->getId(), 5, '0', STR_PAD_LEFT);
                    $receiptPdfPath = $receiptsDir . sprintf('/receipt_%d.pdf', $payment->getId());
                    $receiptPdfUrl = '/media/receipts/' . sprintf('receipt_%d.pdf', $payment->getId());
                    $receiptHtml = $twig->render('invoice/receipt.html.twig', [
                        'user' => $user,
                        'payment' => $payment,
                        'receiptNumber' => $receiptNumber,
                        'issuedAt' => new \DateTime(),
                    ]);
                    $dompdf2 = new Dompdf($options);
                    $dompdf2->loadHtml($receiptHtml, 'UTF-8');
                    $dompdf2->setPaper('A4', 'portrait');
                    $dompdf2->render();
                    $receiptBytes = $dompdf2->output();
                    $bytesWritten2 = @file_put_contents($receiptPdfPath, $receiptBytes);
                    $logger->info('Receipt PDF write attempt', ['path' => $receiptPdfPath, 'bytes' => is_int($bytesWritten2) ? $bytesWritten2 : null, 'ok' => is_int($bytesWritten2) && $bytesWritten2 > 0]);
                    if (!file_exists($receiptPdfPath)) {
                        $logger->error('Receipt PDF not found after write', ['path' => $receiptPdfPath]);
                    }

                    // Save Receipts row
                    $receipt = new \App\Entity\Receipts();
                    $receipt->setPayment($payment)
                        ->setReceiptNumber($receiptNumber)
                        ->setIssuedDate(new \DateTime())
                        ->setPdfurl($receiptPdfUrl);
                    $em->persist($receipt);
                    $em->flush();
                    $logger->info('Receipt persisted', ['paymentId' => $payment->getId(), 'pdfurl' => $receiptPdfUrl]);

                    $emailMsg = (new Email())
            ->from('no-reply@binajia.org')
            ->to($user->getEmail())
            ->subject('Votre adhésion Binajia — Identifiants et carte membre')
            ->html((function() use ($user, $loginUrl, $cardUrlAbs, $txId, $amountTxt, $plainPassword, $isNewUser) {
                $html = '<p>Bonjour ' . htmlspecialchars($user->getFirstname() . ' ' . $user->getLastname()) . ',</p>'
                    . '<p>Votre paiement a été confirmé.</p>';
                if ($isNewUser && $plainPassword) {
                    $html .= '<p><strong>Identifiants de connexion</strong><br>'
                        . 'Email: ' . htmlspecialchars($user->getEmail()) . '<br>'
                        . 'Mot de passe: <code>' . htmlspecialchars($plainPassword) . '</code></p>'
                        . '<p><a href="' . $loginUrl . '">Se connecter</a></p>';
                } else {
                    $html .= '<p>Vous pouvez vous connecter avec vos identifiants existants: <a href="' . $loginUrl . '">Se connecter</a></p>';
                }
                $html .= '<hr>'
                    . '<p><strong>Reçu</strong><br>'
                    . 'Transaction: ' . htmlspecialchars((string)$txId) . '<br>'
                    . 'Montant: ' . htmlspecialchars((string)$amountTxt) . ' XOF<br>'
                    . 'Date: ' . (new \DateTime())->format('d/m/Y H:i') . '</p>'
                    . '<p>Votre carte est disponible ici: <a href="' . $cardUrlAbs . '">' . $cardUrlAbs . '</a></p>';
                return $html;
            })())
            ->attachFromPath($receiptPdfPath, 'recu_binajia.pdf', 'application/pdf');
                    $mailer->send($emailMsg);

                    // Auto-login the user after successful card generation
                    $security->login($user);
                } catch (\Throwable $e) {
                    // Ignore mail errors to not block the flow
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
