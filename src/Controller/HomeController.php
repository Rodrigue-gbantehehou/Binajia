<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function indexA(): Response
    {
        $events = [
            ['id'=>1,'title'=>'Festival des Arts Contemporains','date'=>new \DateTime('+7 days'),'city'=>'Cotonou','coverUrl'=>'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=1200&auto=format&fit=crop','excerpt'=>'Rencontres, performances, expositions.'],
            ['id'=>2,'title'=>'Soirée Highlife & Afrobeat','date'=>new \DateTime('+14 days'),'city'=>'Lagos','coverUrl'=>'https://images.unsplash.com/photo-1564759298141-cef86f51d4d4?q=80&w=1200&auto=format&fit=crop','excerpt'=>'Musique live et danse.'],
        ];
        $places = [
            ['id'=>1,'name'=>'Musée Honmè','city'=>'Porto-Novo','country'=>'Bénin','coverUrl'=>'https://images.unsplash.com/photo-1523805009345-7448845a9e53?q=80&w=1200&auto=format&fit=crop'],
            ['id'=>2,'name'=>'Nike Art Gallery','city'=>'Lagos','country'=>'Nigeria','coverUrl'=>'https://images.unsplash.com/photo-1590736969955-71cc94901144?q=80&w=1200&auto=format&fit=crop'],
        ];
        $stories = [
            ['id'=>1,'title'=>'Textiles yoruba: art et identité','excerpt'=>'Des motifs au sens profond...'],
            ['id'=>2,'title'=>'Bronzes du Bénin: héritage','excerpt'=>'Une histoire millénaire...'],
        ];
        // Render the redesigned homepage by default
        return $this->render('home/index_c.html.twig');
    }

    #[Route('/home/b', name: 'app_home_b')]
    public function indexB(): Response
    {
        $events = [];
        $places = [];
        $stories = [
            ['id'=>1,'title'=>'Textiles yoruba: art et identité','excerpt'=>'Des motifs au sens profond...'],
            ['id'=>2,'title'=>'Bronzes du Bénin: héritage','excerpt'=>'Une histoire millénaire...'],
        ];
        return $this->render('home/index_b.html.twig', compact('events','places','stories'));
    }

    #[Route('/home/c', name: 'app_home_c')]
    public function indexC(): Response
    {
        return $this->render('home/index_c.html.twig');
    }
}
