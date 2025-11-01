<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/partenaire', name: 'admin_partenaire_')]
class PartenaireController extends AbstractController
{
    #[Route('/', name: 'admin_partenaire_index')]
    public function index(): Response
    {
        return $this->render('admin/partenaire/index.html.twig');
    }
}
