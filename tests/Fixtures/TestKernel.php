<?php

namespace Goksagun\SchedulerBundle\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Goksagun\SchedulerBundle\Tests\Fixtures\FooBundle\FooBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new FooBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/config.yml');

        if (PHP_VERSION_ID >= 70100) {
            $loader->load(__DIR__ . '/config/nullable_type/config.yml');
        }
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/cache/' . $this->environment;
    }
}

class_alias('Goksagun\SchedulerBundle\Tests\Fixtures\TestKernel', 'TestKernel');
