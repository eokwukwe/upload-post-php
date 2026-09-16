<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class TotalImpressionsResponse extends ApiResponse
{
    /**
     * @param  array<int|string, mixed>  $raw
     * @param  array<int|string, mixed>  $metrics
     * @param  array<int|string, mixed>  $per_platform
     * @param  array<int|string, mixed>  $per_day
     * @param  list<string>  $platforms_filter
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $profile_username = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?int $total_impressions = null,
        public array $metrics = [],
        public array $per_platform = [],
        public array $per_day = [],
        public array $platforms_filter = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<int|string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'profile_username')),
            self::stringOrNull(Arr::get($raw, 'start_date')),
            self::stringOrNull(Arr::get($raw, 'end_date')),
            self::intOrNull(Arr::get($raw, 'total_impressions')),
            self::arrayOrEmpty(Arr::get($raw, 'metrics')),
            self::arrayOrEmpty(Arr::get($raw, 'per_platform')),
            self::arrayOrEmpty(Arr::get($raw, 'per_day')),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'platforms_filter')), is_string(...))),
        );
    }
}
