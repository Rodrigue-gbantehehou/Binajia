<?php

namespace App\Command;

use App\Service\FileUploader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-avatar-paths',
    description: 'Debug les chemins d\'avatars pour vérifier le format'
)]
class DebugAvatarPathsCommand extends Command
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

        $io->title('🔍 Debug des Chemins d\'Avatars');

        // Simuler un upload d'avatar temporaire
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFilePath = $this->projectDir . '/var/test_debug.png';

        file_put_contents($tempFilePath, $testImageData);

        try {
            $imageData = file_get_contents($tempFilePath);
            $base64Data = base64_encode($imageData);
            $dataUrl = 'data:image/png;base64,' . $base64Data;

            $io->writeln("📤 Upload temporaire...");
            $result = $this->fileUploader->saveAvatarFromDataUrl($dataUrl, null);

            if (isset($result['tempPath'])) {
                $io->success('✅ Upload temporaire réussi');

                // Simuler getRelativePath comme dans les contrôleurs
                $uploadDir = $this->projectDir . '/var/uploads';
                $tempPath = $result['tempPath'];

                $io->writeln("📁 Chemin absolu temporaire: $tempPath");

                // Trouver la partie du chemin qui vient après le dossier d'upload
                $uploadPos = strpos($tempPath, $uploadDir);
                if ($uploadPos !== false) {
                    $relativePath = substr($tempPath, $uploadPos + strlen($uploadDir));
                    // Remplacer les backslashes par des slashes pour la compatibilité web
                    $relativePath = str_replace('\\', '/', $relativePath);

                    $io->writeln("📁 Chemin relatif simulé: $relativePath");

                    // Vérifier que le chemin commence par /membres/
                    if (str_starts_with($relativePath, '/membres/')) {
                        $io->success('✅ Le chemin relatif commence correctement par /membres/');
                    } else {
                        $io->error('❌ Le chemin relatif ne commence pas par /membres/');
                        $io->writeln("   Attendu: /membres/... ");
                        $io->writeln("   Obtenu:  $relativePath");
                        return Command::FAILURE;
                    }

                    // Vérifier que le nom du fichier est temporaire
                    $filename = basename($relativePath);
                    if (str_contains($filename, 'temp_')) {
                        $io->success('✅ Le nom de fichier est temporaire (contient temp_)');
                    } else {
                        $io->error('❌ Le nom de fichier n\'est pas temporaire');
                    }
                } else {
                    $io->error('❌ Impossible de trouver le dossier d\'upload dans le chemin');
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
