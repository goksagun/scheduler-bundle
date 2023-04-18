<?php

namespace Goksagun\SchedulerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SchedulerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $scheduledTaskCommandDefinition = $container->getDefinition('scheduler.service.scheduled_task');
        $scheduledTaskCommandDefinition->replaceArgument(0, $config);

        $scheduledTaskCommandDefinition = $container->getDefinition('scheduler.service.scheduled_task_log');
        $scheduledTaskCommandDefinition->replaceArgument(0, $config);

        $scheduledTaskCommandDefinition = $container->getDefinition('scheduler.command.scheduled_task');
        $scheduledTaskCommandDefinition->replaceArgument(0, $config);

        $scheduledTaskCommandDefinition = $container->getDefinition('scheduler.command.scheduled_task_list');
        $scheduledTaskCommandDefinition->replaceArgument(0, $config);
    }

    public function getAlias(): string
    {
        return 'scheduler';
    }
}
