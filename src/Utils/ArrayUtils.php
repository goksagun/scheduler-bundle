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
     */
    public static function exists(array|\ArrayAccess $array, int|string $key): bool
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Get a subset of the items from the given array.
     */
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Get all the given array except for a specified array of keys.
     */
    public static function except(array $array, array|string $keys): array
    {
        ArrayUtils::forget($array, $keys);

        return $array;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     */
    public static function forget(array &$array, array|string $keys): void
    {
        $original = &$array;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }
}