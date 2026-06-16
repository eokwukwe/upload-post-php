<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class StatusResponse extends ApiResponse
{
    /**
     * @param  array<string,mixed>  $raw
     * @param  array<int|string, mixed>  $results
     */
    public function __construct(
        array $raw,
        public ?string $status = null,
        public ?string $request_id = null,
        public ?string $job_id = null,
        public ?int $completed = null,
        public ?int $total = null,
        public array $results = [],
        public ?string $last_update = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'status')),
            self::stringOrNull(Arr::get($raw, 'request_id')),
            self::stringOrNull(Arr::get($raw, 'job_id')),
            self::intOrNull(Arr::get($raw, 'completed')),
            self::intOrNull(Arr::get($raw, 'total')),
            self::arrayOrEmpty(Arr::get($raw, 'results')),
            self::stringOrNull(Arr::get($raw, 'last_update')),
        );
    }
}
