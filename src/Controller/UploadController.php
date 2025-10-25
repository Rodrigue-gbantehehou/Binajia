<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends AbstractController
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
    }

    /**
     * Sert un fichier depuis var/uploads/media/ ou var/uploads/membres/
     *
     * @param string $filename Chemin relatif depuis 'media/' ou 'membres/' (ex: membres/123.jpg ou cultural/68f177590a7a0.jpg)
     */
    #[Route('/uploads/{filename}', name: 'secure_upload', requirements: ['filename' => '.+'])]
    public function serveFile(string $filename): Response
    {
        // Empêche les traversals de dossier
        $safeFilename = str_replace(['..', './', '.\\'], '', $filename);

        // Vérifier si c'est un fichier membre (avatars)
        if (str_starts_with($safeFilename, 'membres/')) {
            $filePath = $this->uploadDir . '/' . $safeFilename;
        } else {
            // Fichier media traditionnel
            $filePath = $this->uploadDir . '/media/' . $safeFilename;
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable : ' . $safeFilename);
        }

        // Retourne le fichier sans forcer le téléchargement
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $response;
    }
}
