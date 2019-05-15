<?php

namespace Goksagun\SchedulerBundle\Utils;

class TaskHelper
{
    public static function parseName($name)
    {
        return explode(' ', preg_replace('!\s+!', ' ', $name));
    }

    public static function getCommandName($name)
    {
        $parts = static::parseName($name);

        return reset($parts);
    }
}