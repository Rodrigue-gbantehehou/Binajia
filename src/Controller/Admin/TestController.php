<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/test')]
class TestController extends AbstractController
{
    #[Route('/buttons', name: 'admin_test_buttons')]
    public function testButtons(): Response
    {
        return $this->render('admin/test_buttons.html.twig');
    }
}
