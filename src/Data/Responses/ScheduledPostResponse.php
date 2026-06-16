<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class ScheduledPostResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $job_id = null,
        public ?string $scheduled_date = null,
        public ?string $title = null,
        public ?string $caption = null,
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
            self::stringOrNull(Arr::get($raw, 'job_id')),
            self::stringOrNull(Arr::get($raw, 'scheduled_date')),
            self::stringOrNull(Arr::get($raw, 'title')),
            self::stringOrNull(Arr::get($raw, 'caption')),
        );
    }
}
