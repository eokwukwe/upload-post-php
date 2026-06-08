<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Support;

final class Arr
{
    /** 
     * @param array<int|string, mixed> $array 
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $default;
        }

        $current = $array;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    public static function whereNotBlank(array $array): array
    {
        return array_filter($array, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
