<?php

namespace Goksagun\SchedulerBundle\Service;

use Goksagun\SchedulerBundle\Enum\AttributeInterface;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayUtils;
use Goksagun\SchedulerBundle\Utils\HashHelper;
use Symfony\Component\Console\Application;

abstract class AbstractTaskLoader
{
    protected ScheduledTaskService $service;

    protected array $props = [];

    public function __construct(ScheduledTaskService $service)
    {
        $this->service = $service;
    }

    public function supports(?string $resource): bool
    {
        return null === $resource || $resource === static::RESOURCE;
    }

    protected function getApplication(): ?Application
    {
        return $this->service->getApplication();
    }

    protected function generateTaskId(array $annotationTask): string
    {
        return HashHelper::generateIdFromProps(ArrayUtils::only($annotationTask, HashHelper::GENERATED_PROPS));
    }

    protected function getTaskStatus(array $annotationTask): string
    {
        return $annotationTask[AttributeInterface::ATTRIBUTE_STATUS] ?? StatusInterface::STATUS_ACTIVE;
    }
}