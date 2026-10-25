<?php

namespace K3Progetti\SoftwareVersionBundle\Service;

use Composer\InstalledVersions;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Cosa gira davvero in questo progetto: PHP del processo, Symfony e i nostri pacchetti
 * installati in vendor, più hostname e IP della macchina per riconoscere il server.
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
        $json = $this->composerJson();
        if (isset($json['name'])) {
            // Come la sync del server, che il progetto lo ritrova per nome
            $name = str_replace('template/', '', (string)$json['name']);
        }

        return [
            'name' => $name,
            'phpVersion' => PHP_VERSION,
            'symfonyVersion' => $this->installedVersion('symfony/framework-bundle')
                ?: $this->installedVersion('symfony/http-kernel')
                ?: $this->kernelVersion(),
            // In Docker è quello del container, se non si mette hostname: nel compose
            'hostname' => gethostname() ?: null,
            // Solo un'informazione: il server lo riconosce dall'IP pubblico da cui arriva l'invio
            'serverAddr' => isset($_SERVER['SERVER_ADDR']) ? (string)$_SERVER['SERVER_ADDR'] : null,
            'k3Packages' => $this->k3Packages(isset($json['require']) && is_array($json['require']) ? $json['require'] : []),
        ];
    }

    private function composerJson(): array
    {
        $path = $this->kernel->getProjectDir() . '/composer.json';
        if (!is_file($path)) {
            return [];
        }
        $json = json_decode((string)file_get_contents($path), true);

        return is_array($json) ? $json : [];
    }

    /**
     * I nostri pacchetti (k3progetti/...) del require del progetto con la versione installata,
     * es. ["k3progetti/jwt-bundle" => "4.1.0"]. Quelli che arrivano perché li chiede un altro
     * (doctrine-repository dal jwt-bundle) no: nel composer.json non ci sono
     */
    private function k3Packages(array $require): array
    {
        $required = [];
        foreach (array_keys($require) as $name) {
            if (stripos((string)$name, 'k3progetti/') === 0) {
                $required[strtolower((string)$name)] = true;
            }
        }

        $packages = [];
        foreach ($this->installedPackages() as $name => $version) {
            if (isset($required[strtolower($name)])) {
                $packages[strtolower($name)] = $version;
            }
        }
        ksort($packages);

        return $packages;
    }

    /**
     * Tutti i pacchetti in vendor: da InstalledVersions con Composer 2, se no da installed.json
     */
    private function installedPackages(): array
    {
        $packages = [];
        if (class_exists(InstalledVersions::class)) {
            try {
                foreach (InstalledVersions::getInstalledPackages() as $name) {
                    $version = InstalledVersions::getPrettyVersion($name);
                    if ($version !== null) {
                        $packages[$name] = $version;
                    }
                }

                return $packages;
            } catch (Throwable $e) {
                // Proseguo con installed.json
            }
        }

        foreach ($this->installedJson() as $item) {
            if (isset($item['name'], $item['version'])) {
                $packages[$item['name']] = $item['version'];
            }
        }

        return $packages;
    }

    /**
     * Composer 2: {"packages": [...]}, Composer 1: direttamente la lista
     */
    private function installedJson(): array
    {
        $path = $this->kernel->getProjectDir() . '/vendor/composer/installed.json';
        if (!is_file($path)) {
            return [];
        }
        $installed = json_decode((string)file_get_contents($path), true);
        $packages = isset($installed['packages']) ? $installed['packages'] : $installed;

        return is_array($packages) ? $packages : [];
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

        foreach ($this->installedJson() as $item) {
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
