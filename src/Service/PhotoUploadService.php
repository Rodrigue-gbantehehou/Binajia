<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoUploadService
{
    private string $uploadDir;

    public function __construct(
        private SluggerInterface $slugger,
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/public/uploads/photos';
    }

    /**
     * Upload et traite une photo d'utilisateur
     */
    public function uploadUserPhoto(UploadedFile $file, int $userId): array
    {
        try {
            // Créer le répertoire s'il n'existe pas
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            // Générer un nom de fichier unique
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $fileName = sprintf('%s_%d_%s.%s',
                $safeFilename,
                $userId,
                date('YmdHis'),
                $file->guessExtension()
            );

            // Déplacer le fichier
            $file->move($this->uploadDir, $fileName);

            return [
                'success' => true,
                'fileName' => $fileName,
                'filePath' => $this->uploadDir . '/' . $fileName,
                'publicPath' => '/uploads/photos/' . $fileName
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'upload de la photo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Supprime une photo existante
     */
    public function deleteUserPhoto(string $fileName): bool
    {
        $filePath = $this->uploadDir . '/' . $fileName;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Récupère le chemin complet d'une photo
     */
    public function getPhotoPath(string $fileName): ?string
    {
        $filePath = $this->uploadDir . '/' . $fileName;

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }
}
