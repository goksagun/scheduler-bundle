<?php

namespace Goksagun\SchedulerBundle\Utils;

class HashHelper
{
    const GENERATED_PROPS = ['name', 'expression', 'times', 'start', 'stop'];

    public static function generateIdFromProps(array $props): string
    {
        return md5(serialize($props));
    }
}