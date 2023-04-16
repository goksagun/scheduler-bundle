<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\FooBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');

        if (PHP_VERSION_ID >= 70100) {
            $loader->load(__DIR__.'/config/nullable_type/config.yml');
        }
    }

    public function getCacheDir()
    {
        return __DIR__.'/cache/'.$this->environment;
    }
}

class_alias('Goksagun\SchedulerBundle\Tests\Fixtures\TestKernel', 'TestKernel');
