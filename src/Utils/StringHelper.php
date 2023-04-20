<?php

namespace Goksagun\SchedulerBundle\Utils;

final class StringHelper
{
    private function __construct()
    {
    }

    /**
     * Determine if a given string starts with a given substring or an array of substrings.
     *
     * @param string $haystack The string to search in.
     * @param string|array $needles The substring(s) to search for.
     * @return bool True if the haystack starts with any of the given needles, false otherwise.
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
     * Determine if a given string ends with a given substring or one of multiple possible substrings.
     *
     * @param string $haystack The string to search within.
     * @param array|string $needles The substring(s) to search for. Can be a single string or an array of strings.
     * @return bool True if the given string ends with one of the specified substrings, false otherwise.
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
     * Determine if a given string contains any of the given substrings.
     *
     * @param string $haystack The string to search in.
     * @param array|string $needles The substrings to search for.
     * @return bool True if any of the substrings are found, false otherwise.
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
     * Truncates a string to a specified length, adding an ellipsis to the end if necessary.
     *
     * @param string $value The input string to limit.
     * @param int $limit The maximum length of the output string. Defaults to 100.
     * @param string $end The text to append to the end of the truncated string. Defaults to '...'.
     *
     * @return string The truncated string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Interpolate given message with bounded context.
     *
     * @param string $message The message to interpolate.
     * @param array $context The bounded context to use for interpolation.
     * @param string $delimiter The delimiter used to indicate placeholders in the message. Defaults to '{}' if not specified.
     *
     * @return string The interpolated message.
     */
    public static function interpolate(string $message, array $context, string $delimiter = '{}'): string
    {
        $delimiterLength = strlen($delimiter) / 2;
        $openDelim = preg_quote(substr($delimiter, 0, $delimiterLength), '/');
        $closeDelim = preg_quote(substr($delimiter, -$delimiterLength), '/');

        $pattern = sprintf('/%s\s*(\w+)\s*%s/', $openDelim, $closeDelim);

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($context) {
                return $context[$matches[1]] ?? $matches[0];
            },
            $message
        );
    }
}