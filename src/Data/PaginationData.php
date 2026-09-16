<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class PaginationData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $next_cursor = null,
        public ?bool $has_next = null,
        public ?bool $has_more = null,
        public ?int $limit = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'next_cursor')),
            self::boolOrNull(Arr::get($raw, 'has_next')),
            self::boolOrNull(Arr::get($raw, 'has_more')),
            self::intOrNull(Arr::get($raw, 'limit')),
        );
    }
}
