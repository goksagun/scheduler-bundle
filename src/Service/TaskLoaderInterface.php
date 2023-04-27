<?php

namespace Goksagun\SchedulerBundle\Service;

interface TaskLoaderInterface
{
    public function load(?string $status = null, ?string $resource = null): array;
}