<?php

namespace Goksagun\SchedulerBundle\DependencyInjection;

use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scheduler');

        $rootNode = $treeBuilder->getRootNode();

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
