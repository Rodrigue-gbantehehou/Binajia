<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings_index', methods: ['GET'])]
    public function index(): Response
    {
        $env = fn(string $k, $d = null) => $_ENV[$k] ?? $_SERVER[$k] ?? $d;
        $config = [
            'APP_ENV' => $env('APP_ENV', 'dev'),
            'DEFAULT_URI' => $env('DEFAULT_URI'),
            'MAILER_DSN' => $env('MAILER_DSN'),
            'MAIL_FROM_ADDRESS' => $env('MAIL_FROM_ADDRESS') ?? $env('MAIL_FROM_ADRESS'),
            'MAIL_FROM_NAME' => $env('MAIL_FROM_NAME'),
            'FEDAPAY_PUBLIC_KEY' => $env('FEDAPAY_PUBLIC_KEY'),
            'FEDAPAY_SECRET_KEY' => $env('FEDAPAY_SECRET_KEY'),
        ];
        return $this->render('admin/settings/index.html.twig', [ 'config' => $config ]);
    }
}
