<?php

namespace Goksagun\SchedulerBundle\Service;

class ScheduledTaskBuilderFactory
{
    public static function create(string $name, string $expression): ScheduledTaskBuilder
    {
        return new ScheduledTaskBuilder($name, $expression);
    }
}