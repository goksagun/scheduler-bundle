<?php

namespace Goksagun\SchedulerBundle\Enum;

interface ResourceInterface
{
    const RESOURCE_CONFIG = 'config';
    const RESOURCE_ANNOTATION = 'annotation';
    const RESOURCE_DATABASE = 'database';

    const RESOURCES = [
        self::RESOURCE_CONFIG,
        self::RESOURCE_ANNOTATION,
        self::RESOURCE_DATABASE,
    ];
}