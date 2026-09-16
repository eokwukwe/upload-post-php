<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class UsageData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?int $count = null,
        public ?int $limit = null,
        public ?string $last_reset = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::intOrNull(Arr::get($raw, 'count')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::stringOrNull(Arr::get($raw, 'last_reset')),
        );
    }
}
