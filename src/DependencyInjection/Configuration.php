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
            ->scalarNode('endpoint')->defaultValue('https://api.software-version.k3progetti.it/public/software')->end()
            // La chiave dell'azienda, la dà il server delle versioni (non è APP_SECRET: quello non deve uscire dal progetto).
            // Di solito si lascia vuota e il comando legge SOFTWARE_VERSION_KEY quando parte: con %env()% qui,
            // se la variabile manca si romperebbe tutto bin/console, e env(default::) su Symfony 5.4 non passa
            ->scalarNode('key')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
