<?php

namespace Goksagun\SchedulerBundle\Utils;

class TaskHelper
{
    public static function parseName($name)
    {
        $name = preg_replace('/\'/', '"', $name);
        $found = preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $name, $matches);

        if (!$found) {
            return [];
        }

        $parts = $matches[0];

        $temp = [];
        foreach ($parts as $i => $part) {
            if (StringHelper::startsWith($part, ['"', '\'']) && StringHelper::endsWith($part, ['"', '\''])) {
                continue;
            }

            if (StringHelper::contains($part, ['"', '\''])) {
                array_push($temp, $part);

                unset($parts[$i]);
            }
        }

        $chunk = array_chunk($temp, 2);

        foreach ($chunk as $item) {
            array_push($parts, implode(' ', $item));
        }

        $parts = array_map(
            function ($part) {
                return preg_replace('/\"/', '', $part);
            },
            $parts
        );

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

}