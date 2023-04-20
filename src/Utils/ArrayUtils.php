<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Utils;

final class ArrayUtils
{
    private function __construct()
    {
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array|\ArrayAccess $array The array to search for the key.
     * @param int|string $key The key to search for in the array.
     *
     * @return bool True if the key exists in the array, false otherwise.
     */
    public static function exists(array|\ArrayAccess $array, int|string $key): bool
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
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
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array  The target array from which to remove items.
     * @param  array|string  $keys  The key(s) of the item(s) to be removed. Can be a dot-notation string or an array of keys.
     * @return void
     */
    public static function forget(array &$array, array|string $keys): void
    {
        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (ArrayUtils::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $arrayRef = &$array;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($arrayRef[$part]) && is_array($arrayRef[$part])) {
                    $arrayRef = &$arrayRef[$part];
                } else {
                    continue 2;
                }
            }

            unset($arrayRef[array_shift($parts)]);
        }
    }
}