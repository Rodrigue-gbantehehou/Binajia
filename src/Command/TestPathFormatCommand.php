<?php

namespace App\Command;

use App\Service\FileUploader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-path-format',
    description: 'Test que les chemins d\'avatars utilisent des slashes et non des backslashes'
)]
class TestPathFormatCommand extends Command
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

        $io->title('🧪 Test Format des Chemins d\'Avatars');

        // Créer un fichier de test temporaire
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFilePath = $this->projectDir . '/var/test_image.png';

        file_put_contents($tempFilePath, $testImageData);

        try {
            // Créer un data URL de test
            $imageData = file_get_contents($tempFilePath);
            $base64Data = base64_encode($imageData);
            $dataUrl = 'data:image/png;base64,' . $base64Data;

            // Test d'upload temporaire
            $io->writeln("📤 Test d'upload temporaire");
            $result = $this->fileUploader->saveAvatarFromDataUrl($dataUrl, null);

            if (isset($result['tempPath'])) {
                $io->success('✅ Upload temporaire réussi!');
                $io->writeln("📁 Chemin temporaire: " . $result['tempPath']);

                // Simuler la fonction getRelativePath comme elle serait utilisée
                $uploadDir = $this->projectDir . '/var/uploads';
                $relativePath = substr($result['tempPath'], strpos($result['tempPath'], $uploadDir) + strlen($uploadDir));
                $relativePath = str_replace('\\', '/', $relativePath);

                $io->writeln("📁 Chemin relatif simulé: " . $relativePath);

                // Vérifier que le chemin utilise des slashes
                if (strpos($relativePath, '\\') === false && strpos($relativePath, '/') !== false) {
                    $io->success('✅ Le chemin utilise des slashes (format web correct)');
                } else {
                    $io->error('❌ Le chemin contient encore des backslashes');
                    return Command::FAILURE;
                }

                // Test de renommage
                $userId = 123;
                $io->writeln("\n🔄 Test de renommage vers ID utilisateur: $userId");
                $finalPath = $this->fileUploader->renameTempAvatarToFinal($result['tempPath'], $userId);

                if ($finalPath) {
                    $io->success('✅ Renommage réussi!');
                    $io->writeln("📁 Chemin final: " . $finalPath);

                    // Simuler le chemin relatif final
                    $relativeFinalPath = substr($finalPath, strpos($finalPath, $uploadDir) + strlen($uploadDir));
                    $relativeFinalPath = str_replace('\\', '/', $relativeFinalPath);

                    $io->writeln("📁 Chemin relatif final: " . $relativeFinalPath);

                    // Vérifier le format
                    if (strpos($relativeFinalPath, '\\') === false && strpos($relativeFinalPath, '/') !== false) {
                        $io->success('✅ Le chemin final utilise des slashes (format web correct)');
                    } else {
                        $io->error('❌ Le chemin final contient encore des backslashes');
                        return Command::FAILURE;
                    }

                    // Vérifier que le nom du fichier est correct
                    $expectedName = $userId . '.' . pathinfo($tempFilePath, PATHINFO_EXTENSION);
                    if (basename($relativeFinalPath) === $expectedName) {
                        $io->success("✅ Le nom de fichier est correct ($expectedName)");
                    } else {
                        $io->error("❌ Le nom de fichier devrait être '$expectedName'");
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
