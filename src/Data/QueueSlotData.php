<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSlotData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<ScheduledPostData>  $scheduled_posts
     */
    public function __construct(
        array $raw,
        public ?string $datetime_utc = null,
        public ?string $datetime_local = null,
        public ?string $timezone = null,
        public ?bool $available = null,
        public ?int $post_count = null,
        public ?int $max_posts_per_slot = null,
        public ?bool $is_full = null,
        public ?bool $manually_full = null,
        public array $scheduled_posts = [],
        public ?ScheduledPostData $scheduled_post = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $scheduled = Arr::get($raw, 'scheduled_posts');
        $scheduledPost = Arr::get($raw, 'scheduled_post');

        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'datetime_utc')),
            self::stringOrNull(Arr::get($raw, 'datetime_local')),
            self::stringOrNull(Arr::get($raw, 'timezone')),
            self::boolOrNull(Arr::get($raw, 'available')),
            self::intOrNull(Arr::get($raw, 'post_count')),
            self::intOrNull(Arr::get($raw, 'max_posts_per_slot')),
            self::boolOrNull(Arr::get($raw, 'is_full')),
            self::boolOrNull(Arr::get($raw, 'manually_full')),
            is_array($scheduled) ? array_values(array_map(static fn (mixed $post): ScheduledPostData => ScheduledPostData::fromArray(is_array($post) ? $post : []), $scheduled)) : [],
            is_array($scheduledPost) ? ScheduledPostData::fromArray($scheduledPost) : null,
        );
    }
}
