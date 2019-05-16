<?php

namespace Goksagun\SchedulerBundle\Utils;

class TaskHelper
{
    public static function parseName($name)
    {
        $parts = explode(' ', preg_replace('!\s+!', ' ', $name));

        $temp = [];
        foreach ($parts as $i => $part) {
            if (static::contains($part, ['"', '\''])) {
                array_push($temp, $part);

                unset($parts[$i]);
            }
        }

        $chunk = array_chunk($temp, 2);

        foreach ($chunk as $item) {
            array_push($parts, implode(' ', $item));
        }

        return array_values($parts);
    }

    public static function getCommandName($name)
    {
        $parts = static::parseName($name);

        return reset($parts);
    }

    public static function getCommandOptions($name)
    {
        preg_match_all('/(?!\b)(-\w+\b)/', $name, $matches);

        return reset($matches);
    }

    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}