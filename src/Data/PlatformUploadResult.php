<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class PlatformUploadResult extends ResponseData
{
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $platform = null,
        public ?string $status = null,
        public ?string $post_id = null,
        public ?string $publish_id = null,
        public ?string $url = null,
        public ?string $post_url = null,
        public ?string $message = null,
        public ?string $error = null,
        public ?string $error_code = null,
        public ?string $upload_timestamp = null,
        public ?bool $skipped = null,
        public ?string $skip_reason = null,
        public ?string $failure_stage = null,
        public ?string $thumbnail_error = null,
        public ?string $restriction_reason = null,
        public ?string $restricted_until = null,
        public ?int $retry_after_seconds = null,
        public ?bool $fallback_to_inbox = null,
        public ?string $container_id = null,
        public ?string $video_urn = null,
        public ?string $video_reel_id = null,
        public ?string $video_id = null,
        /** @var list<string> */
        public array $image_urns = [],
        /** @var list<string> */
        public array $post_ids = [],
        public ?string $document_urn = null,
        public ?string $content_type = null,
        public ?int $file_size = null,
        public ?string $filename = null,
        public ?string $error_source = null,
        public ?int $linkedin_status = null,
        public ?bool $retryable = null,
        public ?string $article_id = null,
        public ?bool $draft = null,
        public ?bool $photos_were_processed = null,
        public ?bool $video_was_transcoded = null,
        /** @var array<int|string, mixed> */
        public array $changes = [],
        /** @var array<int|string, mixed> */
        public array $changes_per_image = [],
        /** @var array<int|string, mixed> */
        public array $prevalidation_metadata = [],
        public ?bool $first_comment_posted = null,
        /** @var list<string> */
        public array $warnings = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw, ?string $platform = null): self
    {
        $url = self::stringOrNull(Arr::get($raw, 'url') ?? Arr::get($raw, 'post_url'));
        $postUrl = self::stringOrNull(Arr::get($raw, 'post_url') ?? Arr::get($raw, 'url'));

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'platform')) ?? $platform,
            self::stringOrNull(Arr::get($raw, 'status')),
            self::stringOrNull(Arr::get($raw, 'post_id') ?? Arr::get($raw, 'id')),
            self::stringOrNull(Arr::get($raw, 'publish_id')),
            $url,
            $postUrl,
            self::stringOrNull(Arr::get($raw, 'message')),
            self::stringOrNull(Arr::get($raw, 'error')),
            self::stringOrNull(Arr::get($raw, 'error_code')),
            self::stringOrNull(Arr::get($raw, 'upload_timestamp')),
            self::boolOrNull(Arr::get($raw, 'skipped')),
            self::stringOrNull(Arr::get($raw, 'skip_reason')),
            self::stringOrNull(Arr::get($raw, 'failure_stage')),
            self::stringOrNull(Arr::get($raw, 'thumbnail_error')),
            self::stringOrNull(Arr::get($raw, 'restriction_reason')),
            self::stringOrNull(Arr::get($raw, 'restricted_until')),
            self::intOrNull(Arr::get($raw, 'retry_after_seconds')),
            self::boolOrNull(Arr::get($raw, 'fallback_to_inbox')),
            self::stringOrNull(Arr::get($raw, 'container_id')),
            self::stringOrNull(Arr::get($raw, 'video_urn')),
            self::stringOrNull(Arr::get($raw, 'video_reel_id')),
            self::stringOrNull(Arr::get($raw, 'video_id')),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'image_urns')), is_string(...))),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'post_ids')), is_string(...))),
            self::stringOrNull(Arr::get($raw, 'document_urn')),
            self::stringOrNull(Arr::get($raw, 'content_type')),
            self::intOrNull(Arr::get($raw, 'file_size')),
            self::stringOrNull(Arr::get($raw, 'filename')),
            self::stringOrNull(Arr::get($raw, 'error_source')),
            self::intOrNull(Arr::get($raw, 'linkedin_status')),
            self::boolOrNull(Arr::get($raw, 'retryable')),
            self::stringOrNull(Arr::get($raw, 'article_id')),
            self::boolOrNull(Arr::get($raw, 'draft')),
            self::boolOrNull(Arr::get($raw, 'photos_were_processed')),
            self::boolOrNull(Arr::get($raw, 'video_was_transcoded')),
            self::arrayOrEmpty(Arr::get($raw, 'changes')),
            self::arrayOrEmpty(Arr::get($raw, 'changes_per_image')),
            self::arrayOrEmpty(Arr::get($raw, 'prevalidation_metadata')),
            self::boolOrNull(Arr::get($raw, 'first_comment_posted')),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'warnings')), is_string(...))),
        );
    }
}
