<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class TikTokLocationData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $location_id = null,
        public ?string $location_name = null,
        public ?string $location_address = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'location_id')),
            self::stringOrNull(Arr::get($raw, 'location_name')),
            self::stringOrNull(Arr::get($raw, 'location_address')),
        );
    }
}
