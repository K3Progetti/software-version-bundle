<?php

namespace K3Progetti\SoftwareVersionBundle\Service;

use Symfony\Component\HttpKernel\KernelInterface;

final class ComposerInfoService
{
    public function __construct(private KernelInterface $kernel) {}

    public function getInfo(): array
    {
        $path = $this->kernel->getProjectDir().'/composer.json';

        $name = $description = $phpReq = $symfonyReq = $symfonyInstalled = null;

        if (is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $name        = $json['name']        ?? null;
            $description = $json['description'] ?? null;
            $phpReq      = $json['require']['php'] ?? null;
            $symfonyReq  = $json['extra']['symfony']['require']
                ?? $json['require']['symfony/framework-bundle']
                ?? null;
        }

        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $pkg = 'symfony/framework-bundle';
                if (\Composer\InstalledVersions::isInstalled($pkg)) {
                    $symfonyInstalled = \Composer\InstalledVersions::getPrettyVersion($pkg);
                } elseif (\Composer\InstalledVersions::isInstalled('symfony/http-kernel')) {
                    $symfonyInstalled = \Composer\InstalledVersions::getPrettyVersion('symfony/http-kernel');
                }
            } catch (\Throwable) {}
        }

        return [
            'name' => $name,
            'description' => $description,
            'php_requirement' => $phpReq,
            'symfony_requirement' => $symfonyReq,
            'symfony_installed_version' => $symfonyInstalled,
            'php_installed_version' => PHP_VERSION,
        ];
    }
}