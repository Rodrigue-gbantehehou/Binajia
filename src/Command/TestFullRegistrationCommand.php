<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Payments;
use App\Service\EmailService;
use App\Service\MembershipCardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-full-registration',
    description: 'Test complet : inscription + génération carte + envoi emails'
)]
class TestFullRegistrationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,
        private MembershipCardService $cardService,
        private UserPasswordHasherInterface $passwordHasher,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🎯 Test complet d\'inscription BINAJIA');

        try {
            // Étape 1: Créer un utilisateur de test
            $io->section('👤 Création d\'un utilisateur de test');
            $user = $this->createTestUser($io);

            // Étape 2: Créer un paiement de test
            $io->section('💳 Création d\'un paiement de test');
            $payment = $this->createTestPayment($user, $io);

            // Étape 3: Créer un avatar de test
            $io->section('📷 Création d\'un avatar de test');
            $avatarPath = $this->createTestAvatar($io);

            // Étape 4: Générer la carte de membre
            $io->section('🎴 Génération de la carte de membre');
            $cardResult = $this->generateMembershipCard($user, $payment, $avatarPath, $io);

            // Étape 5: Envoyer les emails
            $io->section('📧 Envoi des emails');
            $this->sendEmails($user, $payment, $cardResult, $io);

            // Étape 6: Nettoyer les données de test
            $io->section('🧹 Nettoyage des données de test');
            $this->cleanup($user, $payment, $avatarPath, $cardResult, $io);

            $io->success('🎉 Test complet réussi ! Tous les systèmes fonctionnent correctement.');

        } catch (\Exception $e) {
            $io->error('❌ Erreur pendant le test : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createTestUser(SymfonyStyle $io): User
    {
        $user = new User();
        $user->setEmail('test.' . date('YmdHis') . '@binajia.org');
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');
        $user->setPhone('+229 12 34 56 78');
        $user->setCountry('Bénin');
        $user->setRoles(['ROLE_USER']);
        
        // Mot de passe temporaire
        $tempPassword = 'TempPass123!';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);
        
        $user->setCreatedAt(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        $io->writeln("✅ Utilisateur créé : {$user->getFirstname()} {$user->getLastname()}");
        $io->writeln("📧 Email : {$user->getEmail()}");
        $io->writeln("🔑 Mot de passe temporaire : $tempPassword");

        return $user;
    }

    private function createTestPayment(User $user, SymfonyStyle $io): Payments
    {
        $payment = new Payments();
        $payment->setUser($user);
        $payment->setAmount('25000'); // 25000 FCFA (string selon l'entité)
        $payment->setStatus('completed');
        $payment->setReference('TEST_' . date('YmdHis'));
        $payment->setPaymentMethod('test');
        $payment->setPaymentdate(new \DateTime());

        $this->em->persist($payment);
        $this->em->flush();

        $io->writeln("✅ Paiement créé : {$payment->getAmount()} FCFA");
        $io->writeln("🔗 Référence : {$payment->getReference()}");

        return $payment;
    }

    private function createTestAvatar(SymfonyStyle $io): string
    {
        $avatarDir = $this->projectDir . '/public/media/avatars';
        $avatarPath = '/media/avatars/test_avatar.svg';
        $fullAvatarPath = $this->projectDir . '/public' . $avatarPath;

        // Créer un avatar SVG simple
        $svgContent = <<<SVG
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#f0f0f0"/>
    <circle cx="100" cy="70" r="30" fill="#0a4b1e"/>
    <ellipse cx="100" cy="150" rx="40" ry="25" fill="#0a4b1e"/>
    <text x="100" y="185" text-anchor="middle" font-family="Arial" font-size="12" fill="#666">TEST USER</text>
</svg>
SVG;

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0775, true);
        }

        file_put_contents($fullAvatarPath, $svgContent);

        $io->writeln("✅ Avatar de test créé : $avatarPath");

        return $avatarPath;
    }

    private function generateMembershipCard(User $user, Payments $payment, string $avatarPath, SymfonyStyle $io): array
    {
        $memberId = 'BNJ' . str_pad((string)$user->getId(), 6, '0', STR_PAD_LEFT);

        $result = $this->cardService->generateAndPersist(
            $user,
            $payment,
            $avatarPath,
            $memberId
        );

        $io->writeln("✅ Carte générée avec succès");
        $io->writeln("🎴 URL de la carte : {$result['cardPdfUrl']}");
        $io->writeln("📄 Reçu : " . ($result['receiptPdfPath'] ? 'Généré' : 'Non généré'));
        $io->writeln("🆔 ID Membre : $memberId");

        return $result;
    }

    private function sendEmails(User $user, Payments $payment, array $cardResult, SymfonyStyle $io): void
    {
        $firstName = $user->getFirstname();
        $email = $user->getEmail();

        // 1. Email de bienvenue
        $welcomeResult = $this->emailService->sendWelcomeEmail(
            $email,
            $firstName,
            $user->getLastname(),
            'TempPass123!'
        );

        $io->writeln($welcomeResult ? "✅ Email de bienvenue envoyé" : "❌ Échec email de bienvenue");

        // 2. Email de confirmation de paiement
        $paymentResult = $this->emailService->sendPaymentConfirmationEmail(
            $email,
            $firstName,
            $payment->getReference(),
            $payment->getAmount()
        );

        $io->writeln($paymentResult ? "✅ Email de confirmation de paiement envoyé" : "❌ Échec email de paiement");

        // 3. Email de carte créée
        $cardResult = $this->emailService->sendCardCreatedEmail(
            $email,
            $firstName,
            'BNJ' . str_pad((string)$user->getId(), 6, '0', STR_PAD_LEFT),
            $cardResult['cardPdfUrl']
        );

        $io->writeln($cardResult ? "✅ Email de carte créée envoyé" : "❌ Échec email de carte");
    }

    private function cleanup(User $user, Payments $payment, string $avatarPath, array $cardResult, SymfonyStyle $io): void
    {
        // Supprimer les fichiers générés
        $filesToDelete = [
            $this->projectDir . '/public' . $avatarPath,
            $this->projectDir . '/public' . $cardResult['cardPdfUrl']
        ];

        if ($cardResult['receiptPdfPath']) {
            $filesToDelete[] = $cardResult['receiptPdfPath'];
        }

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                @unlink($file);
                $io->writeln("🗑️ Fichier supprimé : " . basename($file));
            }
        }

        // Supprimer les entités de test (dans l'ordre des dépendances)
        // D'abord supprimer les reçus liés au paiement
        $receipts = $this->em->getRepository(\App\Entity\Receipts::class)->findBy(['payment' => $payment]);
        foreach ($receipts as $receipt) {
            $this->em->remove($receipt);
        }
        
        // Puis supprimer les cartes liées à l'utilisateur
        $cards = $this->em->getRepository(\App\Entity\MembershipCards::class)->findBy(['user' => $user]);
        foreach ($cards as $card) {
            $this->em->remove($card);
        }
        
        // Enfin supprimer le paiement et l'utilisateur
        $this->em->remove($payment);
        $this->em->remove($user);
        $this->em->flush();

        $io->writeln("✅ Données de test nettoyées");
    }
}
