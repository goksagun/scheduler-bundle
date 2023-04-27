<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

interface TaskLoaderInterface
{
    public function load(?string $status = null, ?string $resource = null): array;
}