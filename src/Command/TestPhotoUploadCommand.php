<?php

namespace App\Command;

use App\Service\PhotoUploadService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:test-photo-upload',
    description: 'Test l\'upload et le nommage des photos utilisateur'
)]
class TestPhotoUploadCommand extends Command
{
    public function __construct(
        private PhotoUploadService $photoUploadService,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test Upload et Nommage des Photos');

        // Créer un fichier de test temporaire (image 1x1 pixel PNG en base64)
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFilePath = $this->projectDir . '/var/test_image.png';

        // Créer le fichier temporaire
        file_put_contents($tempFilePath, $testImageData);

        try {
            // Créer un UploadedFile fictif
            $uploadedFile = new UploadedFile(
                $tempFilePath,
                'test_image.png',
                'image/png',
                null,
                true
            );

            // Tester l'upload avec un userId fictif
            $userId = 123;
            $io->writeln("📤 Test d'upload pour l'utilisateur ID: $userId");

            $result = $this->photoUploadService->uploadUserPhoto($uploadedFile, $userId);

            if ($result['success']) {
                $io->success('✅ Upload réussi!');
                $io->writeln("📁 Nom du fichier: " . $result['fileName']);
                $io->writeln("🔗 Chemin public: " . $result['publicPath']);

                // Vérifier que le nom n'est pas "0.jpg"
                if ($result['fileName'] !== '0.jpg') {
                    $io->success("✅ Le nommage est correct (pas '0.jpg')");
                } else {
                    $io->error("❌ Problème: Le fichier s'appelle toujours '0.jpg'");
                    return Command::FAILURE;
                }

                // Nettoyer le fichier temporaire
                unlink($tempFilePath);

                return Command::SUCCESS;
            } else {
                $io->error('❌ Échec de l\'upload: ' . $result['error']);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test: ' . $e->getMessage());
            // Nettoyer le fichier temporaire en cas d'erreur
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            return Command::FAILURE;
        }
    }
}
