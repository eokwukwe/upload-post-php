<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class CommentsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $comments
     * @param  array<int|string, mixed>  $pagination
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $comments = [],
        public array $pagination = [],
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
            return $this->comments;
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
            self::boolOrNull(Arr::get($raw, 'success')),
            self::arrayOrEmpty(Arr::get($raw, 'comments') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::arrayOrEmpty(Arr::get($raw, 'pagination')),
        );
    }
}
