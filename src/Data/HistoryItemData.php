<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class HistoryItemData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $user_email = null,
        public ?string $profile_username = null,
        public ?string $platform = null,
        public ?string $media_type = null,
        public ?string $upload_timestamp = null,
        public ?bool $success = null,
        public mixed $platform_post_id = null,
        public ?string $post_url = null,
        public ?string $error_message = null,
        public ?string $post_title = null,
        public ?string $post_caption = null,
        public ?bool $is_async = null,
        public ?string $job_id = null,
        public ?int $media_size_bytes = null,
        public ?bool $video_was_transcoded = null,
        public ?string $request_id = null,
        public ?int $request_total_platforms = null,
        public mixed $dashboard = null,
        public ?string $external_id = null,
        public ?bool $fallback_to_inbox = null,
        /** @var array<int|string, mixed> */
        public array $changes = [],
        /** @var array<int|string, mixed> */
        public array $prevalidation_metadata = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'user_email')),
            self::stringOrNull(Arr::get($raw, 'profile_username')),
            self::stringOrNull(Arr::get($raw, 'platform')),
            self::stringOrNull(Arr::get($raw, 'media_type')),
            self::stringOrNull(Arr::get($raw, 'upload_timestamp')),
            self::boolOrNull(Arr::get($raw, 'success')),
            Arr::get($raw, 'platform_post_id'),
            self::stringOrNull(Arr::get($raw, 'post_url')),
            self::stringOrNull(Arr::get($raw, 'error_message')),
            self::stringOrNull(Arr::get($raw, 'post_title')),
            self::stringOrNull(Arr::get($raw, 'post_caption')),
            self::boolOrNull(Arr::get($raw, 'is_async')),
            self::stringOrNull(Arr::get($raw, 'job_id')),
            self::intOrNull(Arr::get($raw, 'media_size_bytes')),
            self::boolOrNull(Arr::get($raw, 'video_was_transcoded')),
            self::stringOrNull(Arr::get($raw, 'request_id')),
            self::intOrNull(Arr::get($raw, 'request_total_platforms')),
            Arr::get($raw, 'dashboard'),
            self::stringOrNull(Arr::get($raw, 'external_id')),
            self::boolOrNull(Arr::get($raw, 'fallback_to_inbox')),
            self::arrayOrEmpty(Arr::get($raw, 'changes')),
            self::arrayOrEmpty(Arr::get($raw, 'prevalidation_metadata')),
        );
    }
}
