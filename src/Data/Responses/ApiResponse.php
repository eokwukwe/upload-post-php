<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

abstract readonly class ApiResponse
{
    /** 
     * @param array<int|string, mixed> $raw 
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
}
