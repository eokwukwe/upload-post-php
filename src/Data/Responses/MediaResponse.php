<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\MediaData;
use Softgeng\UploadPost\Data\PaginationData;
use Softgeng\UploadPost\Support\Arr;

final readonly class MediaResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<MediaData>  $media
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $media = [],
        public ?PaginationData $pagination = null,
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
            self::mediaFrom(Arr::get($raw, 'media') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            is_array(Arr::get($raw, 'pagination')) ? PaginationData::fromArray(Arr::get($raw, 'pagination')) : null,
        );
    }
}
