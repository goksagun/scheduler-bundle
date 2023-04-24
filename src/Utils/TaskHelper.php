<?php

namespace Goksagun\SchedulerBundle\Utils;

final class TaskHelper
{
    private function __construct()
    {
    }

    public static function parseName($name): array
    {
        $name = preg_replace('/\'/', '"', $name);
        $found = preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $name, $matches);

        if (!$found) {
            return [];
        }

        $parts = $matches[0];

        $temp = [];
        foreach ($parts as $i => $part) {
            if (StringUtils::startsWith($part, ['"', '\'']) && StringUtils::endsWith($part, ['"', '\''])) {
                continue;
            }

            if (StringUtils::contains($part, ['"', '\''])) {
                $temp[] = $part;

                unset($parts[$i]);
            }
        }

        $chunk = array_chunk($temp, 2);

        foreach ($chunk as $item) {
            $parts[] = implode(' ', $item);
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
        $parts = TaskHelper::parseName($name);

        return reset($parts);
    }

    public static function getCommandOptions($name)
    {
        preg_match_all('/(?!\b)(-\w+\b)/', $name, $matches);

        return reset($matches);
    }

}