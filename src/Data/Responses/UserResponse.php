<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class UserResponse extends ApiResponse
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(array $raw, public ?string $username = null)
    {
        parent::__construct($raw);
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self($raw, self::stringOrNull(Arr::get($raw, 'username') ?? Arr::get($raw, 'user')));
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
