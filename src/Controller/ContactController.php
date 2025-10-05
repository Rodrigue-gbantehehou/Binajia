<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $name = trim((string)$request->request->get('name'));
        $email = trim((string)$request->request->get('email'));
        $message = trim((string)$request->request->get('message'));

        $errors = [];
        if ($name === '') { $errors[] = 'Le nom est requis.'; }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide.'; }
        if ($message === '') { $errors[] = 'Le message est requis.'; }

        if ($errors) {
            foreach ($errors as $e) { $this->addFlash('error', $e); }
            return $this->redirectToRoute('app_contact');
        }

        // TODO: envoyer email ou enregistrer en base
        $this->addFlash('success', 'Merci, votre message a bien été envoyé.');
        return $this->redirectToRoute('app_contact');
    }
}
