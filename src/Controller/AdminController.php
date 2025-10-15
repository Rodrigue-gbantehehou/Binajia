<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboardA(): Response
    {
        return $this->redirectToRoute('adiministration_dashboard');
    }

    #[Route('/admin/b', name: 'admin_dashboard_b')]
    public function dashboardB(): Response
    {
        return $this->dashboardA();
    }
}
