<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class PostAnalyticsPostData extends ResponseData
{
    /** @param array<int|string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $request_id = null,
        public ?string $platform_post_id = null,
        public ?string $platform = null,
        public ?string $profile_username = null,
        public ?string $source = null,
        public ?string $post_title = null,
        public ?string $post_caption = null,
        public ?string $media_type = null,
        public ?string $upload_timestamp = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'request_id')),
            self::stringOrNull(Arr::get($raw, 'platform_post_id')),
            self::stringOrNull(Arr::get($raw, 'platform')),
            self::stringOrNull(Arr::get($raw, 'profile_username')),
            self::stringOrNull(Arr::get($raw, 'source')),
            self::stringOrNull(Arr::get($raw, 'post_title')),
            self::stringOrNull(Arr::get($raw, 'post_caption')),
            self::stringOrNull(Arr::get($raw, 'media_type')),
            self::stringOrNull(Arr::get($raw, 'upload_timestamp')),
        );
    }
}
