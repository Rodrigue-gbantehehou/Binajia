<?php

namespace App\Command;

use App\Entity\User;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-card-display',
    description: 'Test l\'affichage complet de la carte membre générée'
)]
class TestCardDisplayCommand extends Command
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

        $io->title('🖼️ Test Affichage Carte Membre');

        // Récupérer l'utilisateur
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            $io->error("❌ Utilisateur avec ID $userId introuvable");
            return Command::FAILURE;
        }

        $io->writeln("👤 Test pour: {$user->getFirstname()} {$user->getLastname()}");
        $io->writeln("📧 Email: {$user->getEmail()}");
        $io->writeln("📱 Téléphone: {$user->getPhone()}");
        $io->writeln("🖼️ Avatar: {$user->getPhoto()}");

        // Simuler les données pour le template
        $memberId = sprintf('BjNg-%s-%03d', date('Y'), $user->getId());

        $testData = [
            'user' => $user,
            'memberId' => $memberId,
            'avatar' => $user->getPhoto(), // Chemin relatif depuis BD
            'memberCard' => null, // On simule qu'il n'y a pas encore de carte générée
        ];

        // Générer le HTML du template pour vérifier l'affichage
        try {
            $html = $this->pdfGenerator->getTwig()->render('membership/card_generated.html.twig', $testData);

            if (strlen($html) > 1000) {
                $io->success('✅ Template généré avec succès');
                $io->writeln("📏 Taille HTML: " . strlen($html) . " caractères");

                // Vérifier que l'avatar est référencé correctement
                if (str_contains($html, 'path(\'secure_upload\'')) {
                    $io->success('✅ Avatar utilise le contrôleur secure_upload');
                } else {
                    $io->warning('⚠️ Avatar pourrait ne pas utiliser le bon contrôleur');
                }

                // Vérifier que les informations sont présentes
                if (str_contains($html, $user->getFirstname()) && str_contains($html, $user->getLastname())) {
                    $io->success('✅ Informations utilisateur présentes');
                } else {
                    $io->error('❌ Informations utilisateur manquantes');
                    return Command::FAILURE;
                }

            } else {
                $io->error('❌ Template généré mais très petit');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la génération du template: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
