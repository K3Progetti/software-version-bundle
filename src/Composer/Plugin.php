<?php

namespace K3Progetti\SoftwareVersionBundle\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * Plugin Composer: registra il bundle e, chiedendo la password, scrive SOFTWARE_VERSION_KEY nel .env.
 * Serve un plugin perché gli scripts nel composer.json di una dipendenza Composer non li lancia
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    private const BUNDLE_CLASS = 'K3Progetti\SoftwareVersionBundle\SoftwareVersionBundle::class';
    private const ENV_NAME = 'SOFTWARE_VERSION_KEY';

    /** @var IOInterface */
    private $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Quando si toglie il bundle dal progetto
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
        $root = $this->projectRoot();

        $bundlesFile = $root . '/config/bundles.php';
        if (is_file($bundlesFile)) {
            $contents = (string)file_get_contents($bundlesFile);
            $cleaned = preg_replace('/^[ \t]*' . preg_quote(self::BUNDLE_CLASS, '/') . '.*\R?/m', '', $contents);
            if ($cleaned !== null && $cleaned !== $contents) {
                file_put_contents($bundlesFile, $cleaned);
                $io->write('<info>SoftwareVersionBundle tolto da config/bundles.php</info>');
            }
        }

        $configFile = $root . '/config/packages/software_version.yaml';
        if (is_file($configFile)) {
            unlink($configFile);
            $io->write('<info>Tolto config/packages/software_version.yaml</info>');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onInstall',
        ];
    }

    public function onInstall(): void
    {
        $root = $this->projectRoot();
        // Non è un progetto Symfony (es. il bundle stesso), non tocco niente
        if (!is_file($root . '/config/bundles.php')) {
            return;
        }

        $this->registerBundle($root);
        $this->writeKey($root);
    }

    private function registerBundle(string $root): void
    {
        $bundlesFile = $root . '/config/bundles.php';
        $contents = (string)file_get_contents($bundlesFile);
        if (strpos($contents, self::BUNDLE_CLASS) === false) {
            $line = '    ' . self::BUNDLE_CLASS . " => ['all' => true],";
            $newContents = preg_replace('/\n\];\s*$/', "\n" . $line . "\n];\n", rtrim($contents) . "\n", 1, $count);
            if ($count === 1 && $newContents !== null) {
                file_put_contents($bundlesFile, $newContents);
                $this->io->write('<info>SoftwareVersionBundle aggiunto in config/bundles.php</info>');
            } else {
                $this->io->writeError('<warning>Non riesco ad aggiungere SoftwareVersionBundle in config/bundles.php, mettilo a mano</warning>');
            }
        }

        $configFile = $root . '/config/packages/software_version.yaml';
        if (!is_file($configFile) && is_dir(dirname($configFile))) {
            copy(__DIR__ . '/../../resources/config/packages/software_version.yaml.dist', $configFile);
            $this->io->write('<info>Creato config/packages/software_version.yaml</info>');
        }
    }

    private function writeKey(string $root): void
    {
        $envFile = $root . '/.env';
        if (!is_file($envFile)) {
            return;
        }
        $contents = (string)file_get_contents($envFile);
        // Già messa, anche a mano
        if (preg_match('/^' . self::ENV_NAME . '=\S/m', $contents)) {
            return;
        }

        $encrypted = KeyCrypto::load();
        if ($encrypted === null) {
            return;
        }
        // In docker build o in CI nessuno può rispondere
        if (!$this->io->isInteractive()) {
            $this->io->write(sprintf('<comment>%s non c\'è nel .env: lancia composer install a mano per metterla</comment>', self::ENV_NAME));
            return;
        }

        $key = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $password = (string)$this->io->askAndHideAnswer(sprintf('Password per scrivere %s nel .env (invio per saltare): ', self::ENV_NAME));
            if ($password === '') {
                $this->io->write(sprintf('<comment>Salto, %s la metti a mano nel .env</comment>', self::ENV_NAME));
                return;
            }
            $key = KeyCrypto::decrypt($encrypted, $password);
            if ($key !== null) {
                break;
            }
            $this->io->writeError('<error>Password sbagliata</error>');
        }
        if ($key === null) {
            return;
        }

        $line = self::ENV_NAME . '=' . $key;
        // Se c'è già la riga vuota la riempio, sennò la aggiungo in fondo
        $newContents = preg_replace('/^' . self::ENV_NAME . '=[ \t]*$/m', $line, $contents, 1, $count);
        if ($count === 0 || $newContents === null) {
            $newContents = rtrim($contents) . "\n\n" . $line . "\n";
        }
        file_put_contents($envFile, $newContents);
        $this->io->write(sprintf('<info>%s scritta nel .env</info>', self::ENV_NAME));
    }

    private function projectRoot(): string
    {
        $composerFile = realpath(Factory::getComposerFile());

        return $composerFile !== false ? dirname($composerFile) : (string)getcwd();
    }
}
