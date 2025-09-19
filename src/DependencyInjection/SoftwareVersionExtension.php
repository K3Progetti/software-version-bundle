<?php

namespace K3Progetti\SoftwareVersionBundle\DependencyInjection;

use Exception;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SoftwareVersionExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('softwareVersion.endpoint', $config['endpoint']);

        // Carico il services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'softwareVersion';
    }
}
