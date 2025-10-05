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
        $stats = [
            'revenue30d' => 1280,
            'currency' => 'EUR',
            'newMembers30d' => 42,
            'tickets30d' => 310,
            'revenueTotal' => 15400,
            'arpu' => 18.5,
            'users' => 920,
            'eventsUpcoming' => 6,
        ];
        $users = [
            ['id'=>1,'firstName'=>'Ayo','lastName'=>'Mensah','email'=>'ayo@example.com','country'=>'Bénin','active'=>true],
            ['id'=>2,'firstName'=>'Chioma','lastName'=>'Okafor','email'=>'chioma@example.com','country'=>'Nigeria','active'=>true],
        ];
        $events = [
            ['id'=>1,'title'=>'Festival des Arts','date'=>new \DateTime('+20 days'),'city'=>'Cotonou'],
        ];
        $places = [
            ['id'=>1,'name'=>'Nike Art Gallery','city'=>'Lagos','country'=>'Nigeria','coverUrl'=>'/media/place2.jpg'],
        ];
        return $this->render('admin/dashboard.html.twig', compact('stats','users','events','places'));
    }

    #[Route('/admin/b', name: 'admin_dashboard_b')]
    public function dashboardB(): Response
    {
        return $this->dashboardA();
    }
}
