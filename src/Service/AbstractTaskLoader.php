<?php

namespace Goksagun\SchedulerBundle\Service;

use Symfony\Component\Console\Application;

abstract class AbstractTaskLoader
{
    protected ScheduledTaskService $service;

    protected array $props = [];

    public function __construct(ScheduledTaskService $service)
    {
        $this->service = $service;
    }

    public abstract function supports(?string $resource): bool;

    protected function getApplication(): ?Application
    {
        return $this->service->getApplication();
    }
}