<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Component\Console\Application;

class AbstractTaskLoader
{
    protected ScheduledTaskService $service;

    protected array $props = [];

    public function __construct(ScheduledTaskService $service)
    {
        $this->service = $service;
    }

    protected function getApplication(): ?Application
    {
        return $this->service->getApplication();
    }
}