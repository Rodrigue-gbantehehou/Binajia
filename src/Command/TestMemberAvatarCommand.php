<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\MembershipCards;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-member-avatar',
    description: 'Test l\'affichage du vrai avatar d\'un membre existant'
)]
class TestMemberAvatarCommand extends Command
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private EntityManagerInterface $em,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'ID de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');

        $io->title('👤 Test Avatar Membre Existant');

        // Récupérer l'utilisateur
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            $io->error("❌ Utilisateur avec ID $userId introuvable");
            return Command::FAILURE;
        }

        // Récupérer la carte du membre
        $card = $this->em->getRepository(MembershipCards::class)->findOneBy(['user' => $user]);
        if (!$card) {
            $io->error("❌ Carte membre introuvable pour l'utilisateur $userId");
            return Command::FAILURE;
        }

        $io->writeln("👤 Utilisateur: {$user->getFirstname()} {$user->getLastname()}");
        $io->writeln("📧 Email: {$user->getEmail()}");
        $io->writeln("📱 Téléphone: {$user->getPhone()}");

        $avatarPath = $card->getPhoto();
        $io->writeln("🖼️ Chemin avatar en BD: $avatarPath");

        if ($avatarPath) {
            // Préparer les données pour la carte
            $memberId = $card->getCardnumberC() ?? sprintf('BjNg-%s-%03d', date('Y'), $user->getId());
            $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
            $phone = (string)($user->getPhone() ?? '');
            $nationality = (string)($user->getCountry() ?? '');

            $issuedAt = $card->getIssuedate() ?? new \DateTime();
            $expiryAt = $card->getExpiryDate() ?? (new \DateTime())->modify('+1 year');

            // Générer le PDF avec le vrai avatar
            $filename = "test_member_{$user->getId()}_avatar.pdf";
            $outputPath = $this->projectDir . '/var/uploads/cards/' . $filename;

            try {
                $this->pdfGenerator->generatePdf(
                    'membership/card.html.twig',
                    [
                        'avatar' => null, // La méthode prepareAvatar sera appelée dans le service
                        'name' => $name,
                        'phone' => $phone,
                        'nationality' => $nationality,
                        'roleBadge' => 'MEMBRE',
                        'roleTitle' => 'MEMBER\nBINAJIA',
                        'memberId' => $memberId,
                        'expiry' => $expiryAt->format('d/m/Y'),
                        'joinDate' => $issuedAt->format('d/m/Y'),
                        'qrCode' => null,
                    ],
                    $filename,
                    'A4',
                    'portrait',
                    $this->projectDir . '/var/uploads/cards'
                );

                if (file_exists($outputPath)) {
                    $io->success('✅ Carte membre avec avatar générée');
                    $io->writeln("📁 Fichier: $outputPath");
                    $io->writeln("📏 Taille: " . filesize($outputPath) . " octets");
                } else {
                    $io->error('❌ Échec de la génération du PDF');
                    return Command::FAILURE;
                }

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de la génération: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->warning('⚠️ Aucun avatar enregistré pour ce membre');
        }

        return Command::SUCCESS;
    }
}
