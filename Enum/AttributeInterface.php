<?php

namespace Goksagun\SchedulerBundle\Enum;

interface AttributeInterface
{
    const ATTRIBUTE_ID = 'id';
    const ATTRIBUTE_NAME = 'name';
    const ATTRIBUTE_EXPRESSION = 'expression';
    const ATTRIBUTE_TIMES = 'times';
    const ATTRIBUTE_START = 'start';
    const ATTRIBUTE_STOP = 'stop';
    const ATTRIBUTE_STATUS = 'status';
    const ATTRIBUTE_RESOURCE = 'resource';

    const ATTRIBUTES = [
        self::ATTRIBUTE_ID,
        self::ATTRIBUTE_NAME,
        self::ATTRIBUTE_EXPRESSION,
        self::ATTRIBUTE_TIMES,
        self::ATTRIBUTE_START,
        self::ATTRIBUTE_STOP,
        self::ATTRIBUTE_STATUS,
        self::ATTRIBUTE_RESOURCE,
    ];
}