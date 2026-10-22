<?php

namespace K3Progetti\SoftwareVersionBundle\Service;

use Composer\InstalledVersions;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Cosa gira davvero in questo progetto: PHP del processo e Symfony installato in vendor.
 * Scritto per PHP 7.2 apposta (niente typed properties, arrow function, str_contains...):
 * deve andare anche sui progetti vecchi, sono quelli che ci interessano di più
 */
final class ComposerInfoService
{
    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getInfo(): array
    {
        $name = null;
        $path = $this->kernel->getProjectDir() . '/composer.json';
        if (is_file($path)) {
            $json = json_decode((string)file_get_contents($path), true);
            if (is_array($json) && isset($json['name'])) {
                // Come la sync del server, che il progetto lo ritrova per nome
                $name = str_replace('template/', '', (string)$json['name']);
            }
        }

        return [
            'name' => $name,
            'phpVersion' => PHP_VERSION,
            'symfonyVersion' => $this->installedVersion('symfony/framework-bundle')
                ?: $this->installedVersion('symfony/http-kernel')
                ?: $this->kernelVersion(),
        ];
    }

    /**
     * Da Composer 2 c'è InstalledVersions, con Composer 1 leggo installed.json a mano
     */
    private function installedVersion(string $package): ?string
    {
        if (class_exists(InstalledVersions::class)) {
            try {
                if (InstalledVersions::isInstalled($package)) {
                    return InstalledVersions::getPrettyVersion($package);
                }
            } catch (Throwable $e) {
                // Proseguo con installed.json
            }
        }

        $path = $this->kernel->getProjectDir() . '/vendor/composer/installed.json';
        if (!is_file($path)) {
            return null;
        }
        $installed = json_decode((string)file_get_contents($path), true);
        // Composer 2: {"packages": [...]}, Composer 1: direttamente la lista
        $packages = isset($installed['packages']) ? $installed['packages'] : $installed;
        foreach (is_array($packages) ? $packages : [] as $item) {
            if (isset($item['name'], $item['version']) && $item['name'] === $package) {
                return $item['version'];
            }
        }

        return null;
    }

    private function kernelVersion(): ?string
    {
        $kernel = 'Symfony\Component\HttpKernel\Kernel';

        return defined($kernel . '::VERSION') ? constant($kernel . '::VERSION') : null;
    }
}
