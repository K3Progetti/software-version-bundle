<?php

namespace K3Progetti\SoftwareVersionBundle\Service;

use Composer\InstalledVersions;
use JsonException;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class ComposerInfoService
{
    public function __construct(
        private KernelInterface $kernel
    )
    {
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function getInfo(): array
    {
        $path = $this->kernel->getProjectDir() . '/composer.json';

        $name = $description = $phpReq = $symfonyReq = $symfonyInstalled = null;

        if (is_file($path)) {
            $json = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $name = $json['name'] ?? null;
            $description = $json['description'] ?? null;
            $phpReq = $json['require']['php'] ?? null;
            $symfonyReq = $json['extra']['symfony']['require']
                ?? $json['require']['symfony/framework-bundle']
                ?? null;
        }

        if (class_exists(InstalledVersions::class)) {
            try {
                $pkg = 'symfony/framework-bundle';
                if (InstalledVersions::isInstalled($pkg)) {
                    $symfonyInstalled = InstalledVersions::getPrettyVersion($pkg);
                } elseif (InstalledVersions::isInstalled('symfony/http-kernel')) {
                    $symfonyInstalled = InstalledVersions::getPrettyVersion('symfony/http-kernel');
                }
            } catch (Throwable) {
            }
        }

        return [
            'name' => str_replace('template/', '', $name),
            'description' => $description,
            'phpRequirement' => $phpReq,
            'symfonyRequirement' => $symfonyReq,
            'symfonyInstalledVersion' => $symfonyInstalled,
            'phpInstalledVersion' => PHP_VERSION
        ];
    }
}