<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Utils;

final class ArrayUtils
{
    private function __construct()
    {
    }

    /**
     * Determine if the given key exists in the provided array using "dot" notation.
     *
     * @param array $array The array to search for the key.
     * @param string $key The key to search for in the array using "dot" notation.
     *
     * @return bool True if the key exists in the array, false otherwise.
     */
    public static function exists(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }


    /**
     * Get a subset of the items from the given array, containing only the specified keys.
     *
     * @param array $array The array to extract items from.
     * @param array|string $keys The keys to extract from the array. Can be either an array of keys or a single key as a string.
     *
     * @return array An array containing only the items with the specified keys.
     */
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Get all the items from the given array except for a specified array of keys.
     *
     * @param array $array The array to retrieve items from.
     * @param array|string $keys The keys to exclude from the resulting array.
     *
     * @return array The resulting array with the specified keys excluded.
     */
    public static function except(array $array, array|string $keys): array
    {
        ArrayUtils::forget($array, $keys);

        return $array;
    }

    /**
     * Remove one or many items from the provided array using "dot" notation.
     *
     * @param array $array The array to remove items from.
     * @param array|string $keys The dot notation key(s) of the item(s) to remove.
     *
     * @return void
     */
    public static function forget(array &$array, array|string $keys): void
    {
        foreach ((array)$keys as $key) {
            if (str_contains($key, '.')) {
                $segments = explode('.', $key);
                $reference =& $array;

                while (count($segments) > 1) {
                    $segment = array_shift($segments);

                    if (isset($reference[$segment]) && is_array($reference[$segment])) {
                        $reference =& $reference[$segment];
                    } else {
                        continue 2;
                    }
                }

                unset($reference[array_shift($segments)]);
            } else {
                unset($array[$key]);
            }
        }
    }
}