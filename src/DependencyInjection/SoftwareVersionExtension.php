<?php

namespace K3Progetti\SoftwareVersionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SoftwareVersionExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('software_version.endpoint', $config['endpoint']);
        $container->setParameter('software_version.key', $config['key']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../resources/config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'software_version';
    }
}
