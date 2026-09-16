<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\ScheduledPostData;
use Softgeng\UploadPost\Support\Arr;

final readonly class ScheduledPostsResponse extends ApiResponse
{
    /**
     * @param  array<int|string, mixed>  $raw
     * @param  list<ScheduledPostData>  $scheduled_posts
     */
    public function __construct(
        array $raw,
        public array $scheduled_posts = [],
        public ?int $total = null,
        public ?int $limit = null,
        public ?int $offset = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<int|string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::scheduledPostsFrom(
                array_is_list($raw) ? $raw : (Arr::get($raw, 'scheduled_posts') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            ),
            self::intOrNull(Arr::get($raw, 'total')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::intOrNull(Arr::get($raw, 'offset')),
        );
    }
}
