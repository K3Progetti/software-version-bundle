<?php

namespace K3Progetti\SoftwareVersionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('software_version');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('endpoint')->defaultValue('https://api.software-version.k3progetti.it')->end()
            ->end();

        return $treeBuilder;
    }
}
