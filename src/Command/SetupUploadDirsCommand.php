<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-upload-dirs',
    description: 'Crée les dossiers d\'upload nécessaires pour les PDFs'
)]
class SetupUploadDirsCommand extends Command
{
    public function __construct(private string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📁 Configuration des dossiers d\'upload');

        $uploadDir = $this->projectDir . '/var/uploads';
        $dirsToCreate = [
            'pdf' => $uploadDir . '/pdf',
            'cards' => $uploadDir . '/cards',
            'receipts' => $uploadDir . '/receipts',
            'avatars' => $uploadDir . '/avatars',
            'private' => $uploadDir . '/private',
        ];

        foreach ($dirsToCreate as $name => $path) {
            if (!is_dir($path)) {
                if (mkdir($path, 0775, true)) {
                    $io->writeln("✅ Dossier créé: $name ($path)");
                } else {
                    $io->error("❌ Échec création dossier: $name ($path)");
                    return Command::FAILURE;
                }
            } else {
                $io->writeln("ℹ️ Dossier existe déjà: $name ($path)");
            }
        }

        $io->success('🎉 Tous les dossiers d\'upload ont été configurés!');

        return Command::SUCCESS;
    }
}
