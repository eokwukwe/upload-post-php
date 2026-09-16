<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class ResponseItem extends ResponseData
{
    public function __construct(
        array $raw,
        public ?string $id = null,
        public ?string $platform = null,
        public ?string $title = null,
        public ?string $status = null,
        public ?string $url = null,
        public ?string $created_at = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'id') ?? Arr::get($raw, 'job_id') ?? Arr::get($raw, 'name') ?? Arr::get($raw, 'username')),
            self::stringOrNull(Arr::get($raw, 'platform')),
            self::stringOrNull(Arr::get($raw, 'title') ?? Arr::get($raw, 'name') ?? Arr::get($raw, 'username')),
            self::stringOrNull(Arr::get($raw, 'status')),
            self::stringOrNull(Arr::get($raw, 'url') ?? Arr::get($raw, 'permalink')),
            self::stringOrNull(Arr::get($raw, 'created_at') ?? Arr::get($raw, 'createdAt')),
        );
    }
}
