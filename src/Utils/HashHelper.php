<?php

namespace Goksagun\SchedulerBundle\Utils;

class HashHelper
{
    const GENERATED_PROPS = ['name', 'expression', 'times', 'start', 'stop'];

    public static function generateIdFromProps(array $props)
    {
        return md5(serialize($props));
    }
}