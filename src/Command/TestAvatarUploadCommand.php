<?php

namespace App\Command;

use App\Service\FileUploader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-avatar-upload',
    description: 'Test l\'upload et le renommage des avatars utilisateur'
)]
class TestAvatarUploadCommand extends Command
{
    public function __construct(
        private FileUploader $fileUploader,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test Upload et Nommage des Avatars');

        // Créer un fichier de test temporaire (image 1x1 pixel PNG en base64)
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFilePath = $this->projectDir . '/var/test_image.png';

        // Créer le fichier temporaire
        file_put_contents($tempFilePath, $testImageData);

        try {
            // Créer un data URL de test
            $imageData = file_get_contents($tempFilePath);
            $base64Data = base64_encode($imageData);
            $dataUrl = 'data:image/png;base64,' . $base64Data;

            // Test 1: Upload temporaire
            $io->writeln("📤 Test d'upload temporaire (sans ID utilisateur)");
            $result = $this->fileUploader->saveAvatarFromDataUrl($dataUrl, null);

            if (isset($result['tempPath'])) {
                $io->success('✅ Upload temporaire réussi!');
                $io->writeln("📁 Fichier temporaire: " . basename($result['tempPath']));
                $io->writeln("📁 Nom final attendu: " . basename($result['finalPath']));

                // Vérifier que le nom temporaire n'est pas "0.jpg"
                $tempBasename = basename($result['tempPath']);
                if (!preg_match('/^0\.(jpg|png)$/', $tempBasename)) {
                    $io->success("✅ Le nommage temporaire est correct (pas '0.jpg')");
                } else {
                    $io->error("❌ Problème: Le fichier temporaire s'appelle '0.jpg'");
                    return Command::FAILURE;
                }

                // Test 2: Renommage avec un ID utilisateur fictif
                $userId = 123;
                $io->writeln("\n🔄 Test de renommage vers ID utilisateur: $userId");
                $finalPath = $this->fileUploader->renameTempAvatarToFinal($result['tempPath'], $userId);

                if ($finalPath) {
                    $io->success('✅ Renommage réussi!');
                    $io->writeln("📁 Fichier final: " . basename($finalPath));

                    // Vérifier que le nom final est correct
                    $finalBasename = basename($finalPath);
                    $expectedName = $userId . '.' . pathinfo($tempFilePath, PATHINFO_EXTENSION);

                    if ($finalBasename === $expectedName) {
                        $io->success("✅ Le nommage final est correct ($expectedName)");
                    } else {
                        $io->error("❌ Problème: Le fichier final s'appelle '$finalBasename' au lieu de '$expectedName'");
                        return Command::FAILURE;
                    }
                } else {
                    $io->error('❌ Échec du renommage');
                    return Command::FAILURE;
                }

                return Command::SUCCESS;
            } else {
                $io->error('❌ Échec de l\'upload temporaire');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test: ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // Nettoyer le fichier temporaire
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }
}
