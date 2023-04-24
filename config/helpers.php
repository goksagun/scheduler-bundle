<?php

use Goksagun\SchedulerBundle\Utils\StringUtils;

if (!function_exists('limit')) {
    function limit(string $input, int $length = 100, string $indicator = '...'): string
    {
        return StringUtils::limit($input, $length, $indicator);
    }
}

if (!function_exists('interpolate')) {
    function interpolate(string $message, array $context, string $delimiter = '{}'): string
    {
        return StringUtils::interpolate($message, $context, $delimiter);
    }
}
