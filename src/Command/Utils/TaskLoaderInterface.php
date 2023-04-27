<?php

namespace Goksagun\SchedulerBundle\Command\Utils;

interface TaskLoaderInterface
{
    public function load(): array;
}