<?php

namespace Goksagun\SchedulerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('scheduler');

        $rootNode
            ->children()
                ->booleanNode('enable')->defaultValue(true)->end()
            ->end()
            ->children()
                ->scalarNode('async')->defaultValue(null)->end()
            ->end()
            ->children()
                ->scalarNode('log')->defaultValue(null)->end()
            ->end()
            ->children()
                ->arrayNode('tasks')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('expression')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
