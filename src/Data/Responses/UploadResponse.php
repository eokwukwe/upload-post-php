<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class UploadResponse extends ApiResponse
{
    /** @param array<int|string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $request_id = null,
        public ?string $job_id = null,
        public ?string $status = null,
        public ?string $message = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<int|string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            raw: $raw,
            request_id: self::stringOrNull(Arr::get($raw, 'request_id')),
            job_id: self::stringOrNull(Arr::get($raw, 'job_id')),
            status: self::stringOrNull(Arr::get($raw, 'status')),
            message: self::stringOrNull(Arr::get($raw, 'message')),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
