<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

abstract readonly class ApiResponse
{
    /**
     * @param  array<int|string, mixed>  $raw
     */
    public function __construct(public array $raw) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->raw, $key, $default);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    protected static function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    protected static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
