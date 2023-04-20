<?php

namespace Goksagun\SchedulerBundle\Utils;

final class StringHelper
{
    private function __construct()
    {
    }

    /**
     * Determine if a given string starts with a given substring.
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     */
    public static function endsWith(string $haystack, array|string $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if (str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string contains a given substring.
     */
    public static function contains(string $haystack, array|string $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    public static function interpolate(string $message, array $context): string
    {
        return preg_replace_callback(
            '/{{\s*(\w+)\s*}}/',
            function ($matches) use ($context) {
                return $context[$matches[1]] ?? $matches[0];
            },
            $message
        );
    }
}