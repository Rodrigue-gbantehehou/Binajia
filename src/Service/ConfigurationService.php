<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationService
{
    public function __construct(
        private ParameterBagInterface $params
    ) {}

    public function getFromEmail(): string
    {
        return $_ENV['MAIL_FROM_ADDRESS'] ?? 'contact@binajia.org';
    }

    public function getFromName(): string
    {
        return $_ENV['MAIL_FROM_NAME'] ?? 'BINAJIA';
    }

    public function getBaseUrl(): string
    {
        // En développement, utiliser localhost, en production utiliser le domaine réel
        $env = $_ENV['APP_ENV'] ?? 'dev';
        
        if ($env === 'prod') {
            return 'https://binajia.org';
        }
        
        return $_ENV['DEFAULT_URI'] ?? 'http://localhost';
    }

    public function getLoginUrl(): string
    {
        return $this->getBaseUrl() . '/login';
    }

    public function getDashboardUrl(): string
    {
        return $this->getBaseUrl() . '/dashboard';
    }

    public function getDownloadUrl(string $path): string
    {
        return $this->getBaseUrl() . $path;
    }
}
