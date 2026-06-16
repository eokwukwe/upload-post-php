<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class ScheduledPostsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $scheduled_posts
     */
    public function __construct(
        array $raw,
        public array $scheduled_posts = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * Backward-compatible alias for older ListResponse usage.
     *
     * @return array<int|string, mixed>|null
     */
    public function __get(string $name): mixed
    {
        if ($name === 'items') {
            return $this->scheduled_posts;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::arrayOrEmpty(Arr::get($raw, 'scheduled_posts') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
        );
    }
}
