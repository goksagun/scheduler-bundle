<?php

namespace Goksagun\SchedulerBundle\DependencyInjection;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scheduler');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // symfony < 4.2 support
            $rootNode = $treeBuilder->root('scheduler');
        }

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
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
                            ->scalarNode('times')->defaultNull()->end()
                            ->scalarNode('start')->defaultNull()->end()
                            ->scalarNode('stop')->defaultNull()->end()
                            ->scalarNode('status')->defaultValue(StatusInterface::STATUS_ACTIVE)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
