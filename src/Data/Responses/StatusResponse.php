<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class StatusResponse extends ApiResponse
{
    /** @param array<string,mixed> $raw */
    public function __construct(array $raw, public ?string $status = null, public ?string $request_id = null, public ?string $job_id = null)
    {
        parent::__construct($raw);
    }

    /** @param array<string,mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'status')),
            self::stringOrNull(Arr::get($raw, 'request_id')),
            self::stringOrNull(Arr::get($raw, 'job_id')),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
