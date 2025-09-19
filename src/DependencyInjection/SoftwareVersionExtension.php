<?php

namespace K3Progetti\SoftwareVersionBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SoftwareVersionExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // param naming consigliato in snake_case
        $container->setParameter('software_version.endpoint', $config['endpoint']);

        // Usa il path corretto a seconda di come hai nominato la cartella:
        // Se è "Resources/config":
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        // Se invece hai realmente "resources/config", usa la riga sotto e commenta la precedente:
        // $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../resources/config'));

        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        // Deve essere snake_case del nome bundle senza "Bundle"
        return 'software_version';
    }
}
