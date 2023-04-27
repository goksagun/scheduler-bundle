<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

use Goksagun\SchedulerBundle\Service\ScheduledTaskService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class AbstractTaskLoader
{
    protected ScheduledTaskService $service;

    protected ?string $status = null;

    protected array $props = [];

    protected array $tasks = [];

    public function __construct(ScheduledTaskService $service)
    {
        $this->service = $service;
    }

    protected function getApplication(): ?Application
    {
        return $this->service->getApplication();
    }
}