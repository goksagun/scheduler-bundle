<?php

use Goksagun\SchedulerBundle\Utils\StringUtils;

if (!function_exists('limit')) {
    function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return StringUtils::limit($value, $limit, $end);
    }
}

if (!function_exists('interpolate')) {
    function interpolate(string $message, array $context, string $delimiter = '{}'): string
    {
        return StringUtils::interpolate($message, $context, $delimiter);
    }
}
