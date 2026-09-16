<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class MediaData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $id = null,
        public ?string $caption = null,
        public ?string $media_type = null,
        public ?string $media_url = null,
        public ?string $permalink = null,
        public ?string $timestamp = null,
        public ?string $thumbnail_url = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'id')),
            self::stringOrNull(Arr::get($raw, 'caption')),
            self::stringOrNull(Arr::get($raw, 'media_type')),
            self::stringOrNull(Arr::get($raw, 'media_url')),
            self::stringOrNull(Arr::get($raw, 'permalink')),
            self::stringOrNull(Arr::get($raw, 'timestamp')),
            self::stringOrNull(Arr::get($raw, 'thumbnail_url')),
        );
    }
}
