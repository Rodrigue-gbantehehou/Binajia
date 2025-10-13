<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-email-card',
    description: 'Corrige automatiquement les problèmes d\'email et de génération de cartes'
)]
class FixEmailCardCommand extends Command
{
    public function __construct(
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔧 Correction automatique des problèmes Email et Cartes - BINAJIA');

        // Fix 1: Créer les dossiers nécessaires
        $io->section('📁 Création des dossiers nécessaires');
        $this->createDirectories($io);

        // Fix 2: Vérifier les templates d'emails
        $io->section('📧 Vérification des templates d\'emails');
        $this->checkEmailTemplates($io);

        // Fix 3: Créer un avatar par défaut
        $io->section('👤 Création d\'un avatar par défaut');
        $this->createDefaultAvatar($io);

        // Fix 4: Nettoyer le cache
        $io->section('🧹 Nettoyage du cache');
        $this->clearCache($io);

        $io->success('Corrections terminées ! Testez maintenant avec: php bin/console app:test-email-card');

        return Command::SUCCESS;
    }

    private function createDirectories(SymfonyStyle $io): void
    {
        $directories = [
            '/public/media',
            '/public/media/cards',
            '/public/media/receipts',
            '/public/media/avatars',
            '/public/media/temp',
            '/var/log',
        ];

        foreach ($directories as $dir) {
            $fullPath = $this->projectDir . $dir;
            
            if (!is_dir($fullPath)) {
                if (@mkdir($fullPath, 0775, true)) {
                    $io->writeln("✅ Dossier créé: $dir");
                } else {
                    $io->writeln("❌ Impossible de créer: $dir");
                }
            } else {
                $io->writeln("✅ Dossier existe: $dir");
            }

            // Créer un fichier .gitkeep pour préserver le dossier
            $gitkeepPath = $fullPath . '/.gitkeep';
            if (!file_exists($gitkeepPath)) {
                file_put_contents($gitkeepPath, '');
            }
        }
    }

    private function checkEmailTemplates(SymfonyStyle $io): void
    {
        $templates = [
            'welcome.html.twig',
            'card_created.html.twig',
            'payment_request.html.twig',
            'payment_confirmation.html.twig'
        ];

        $templatesDir = $this->projectDir . '/templates/emails';

        foreach ($templates as $template) {
            $templatePath = $templatesDir . '/' . $template;
            
            if (file_exists($templatePath)) {
                $io->writeln("✅ Template existe: $template");
            } else {
                $io->writeln("❌ Template manquant: $template");
                $this->createBasicEmailTemplate($templatePath, $template, $io);
            }
        }
    }

    private function createBasicEmailTemplate(string $path, string $name, SymfonyStyle $io): void
    {
        $basicTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BINAJIA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #0a4b1e; }
        .content { line-height: 1.6; color: #333; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">BINAJIA</div>
            <p>Association Bénino-Nigériane</p>
        </div>
        <div class="content">
            <h2>Email de BINAJIA</h2>
            <p>Ceci est un template d'email basique pour $name</p>
            <p>Veuillez personnaliser ce template selon vos besoins.</p>
        </div>
        <div class="footer">
            <p>© BINAJIA - Association Bénino-Nigériane</p>
            <p>binajia@hotmail.com | www.binajia.org</p>
        </div>
    </div>
</body>
</html>
HTML;

        if (file_put_contents($path, $basicTemplate)) {
            $io->writeln("✅ Template basique créé: $name");
        } else {
            $io->writeln("❌ Impossible de créer le template: $name");
        }
    }

    private function createDefaultAvatar(SymfonyStyle $io): void
    {
        $avatarPath = $this->projectDir . '/public/media/avatars/default.jpg';
        
        if (!file_exists($avatarPath)) {
            // Créer une image SVG par défaut
            $defaultSvg = <<<SVG
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#e0e0e0"/>
    <circle cx="100" cy="80" r="30" fill="#ccc"/>
    <ellipse cx="100" cy="160" rx="50" ry="30" fill="#ccc"/>
    <text x="100" y="190" text-anchor="middle" font-family="Arial" font-size="12" fill="#666">Avatar</text>
</svg>
SVG;

            $svgPath = $this->projectDir . '/public/media/avatars/default.svg';
            if (file_put_contents($svgPath, $defaultSvg)) {
                $io->writeln("✅ Avatar par défaut créé: default.svg");
            } else {
                $io->writeln("❌ Impossible de créer l'avatar par défaut");
            }
        } else {
            $io->writeln("✅ Avatar par défaut existe déjà");
        }
    }

    private function clearCache(SymfonyStyle $io): void
    {
        $cacheDir = $this->projectDir . '/var/cache';
        
        if (is_dir($cacheDir)) {
            try {
                $this->deleteDirectory($cacheDir);
                $io->writeln("✅ Cache supprimé");
            } catch (\Exception $e) {
                $io->writeln("❌ Erreur lors de la suppression du cache: " . $e->getMessage());
            }
        } else {
            $io->writeln("✅ Pas de cache à supprimer");
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        @rmdir($dir);
    }
}
