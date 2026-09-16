<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\CommentData;
use Softgeng\UploadPost\Data\PaginationData;
use Softgeng\UploadPost\Support\Arr;

final readonly class CommentsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<CommentData>  $comments
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $comments = [],
        public ?PaginationData $pagination = null,
        public ?bool $partial = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::commentsFrom(Arr::get($raw, 'comments') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            is_array(Arr::get($raw, 'pagination')) ? PaginationData::fromArray(Arr::get($raw, 'pagination')) : null,
            self::boolOrNull(Arr::get($raw, 'partial')),
        );
    }
}
