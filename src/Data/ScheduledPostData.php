<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class ScheduledPostData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $platforms
     * @param  array<int|string, mixed>  $platform_content
     * @param  array<int|string, mixed>  $fields
     */
    public function __construct(
        array $raw,
        public ?string $job_id = null,
        public ?string $scheduled_date = null,
        public ?string $post_type = null,
        public ?string $profile_username = null,
        public ?string $title = null,
        public ?string $preview_url = null,
        public array $platforms = [],
        public ?string $external_id = null,
        public ?string $source_filename = null,
        public ?string $caption = null,
        public ?string $description = null,
        public array $platform_content = [],
        public array $fields = [],
        public ?bool $has_cover = null,
        public ?string $cover_preview_url = null,
        public ?bool $has_preview = null,
        public ?string $thumbnail_url = null,
        public ?string $original_timezone = null,
        public ?string $original_scheduled_str = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'job_id')),
            self::stringOrNull(Arr::get($raw, 'scheduled_date')),
            self::stringOrNull(Arr::get($raw, 'post_type')),
            self::stringOrNull(Arr::get($raw, 'profile_username')),
            self::stringOrNull(Arr::get($raw, 'title')),
            self::stringOrNull(Arr::get($raw, 'preview_url')),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'platforms')), is_string(...))),
            self::stringOrNull(Arr::get($raw, 'external_id')),
            self::stringOrNull(Arr::get($raw, 'source_filename')),
            self::stringOrNull(Arr::get($raw, 'caption')),
            self::stringOrNull(Arr::get($raw, 'description')),
            self::arrayOrEmpty(Arr::get($raw, 'platform_content')),
            self::arrayOrEmpty(Arr::get($raw, 'fields')),
            self::boolOrNull(Arr::get($raw, 'has_cover')),
            self::stringOrNull(Arr::get($raw, 'cover_preview_url')),
            self::boolOrNull(Arr::get($raw, 'has_preview')),
            self::stringOrNull(Arr::get($raw, 'thumbnail_url')),
            self::stringOrNull(Arr::get($raw, 'original_timezone')),
            self::stringOrNull(Arr::get($raw, 'original_scheduled_str')),
        );
    }
}
