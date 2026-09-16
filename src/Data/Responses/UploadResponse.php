<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\PlatformUploadResult;
use Softgeng\UploadPost\Data\UsageData;
use Softgeng\UploadPost\Support\Arr;

final readonly class UploadResponse extends ApiResponse
{
    /**
     * @param  array<int|string, mixed>  $raw
     * @param  list<PlatformUploadResult>  $results
     */
    public function __construct(
        array $raw,
        public ?string $request_id = null,
        public ?string $job_id = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?bool $success = null,
        public array $results = [],
        public ?string $external_id = null,
        public ?int $total_platforms = null,
        public ?string $scheduled_date = null,
        public ?UsageData $usage = null,
        /** @var list<string> */
        public array $warnings = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<int|string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            raw: $raw,
            request_id: self::stringOrNull(Arr::get($raw, 'request_id')),
            job_id: self::stringOrNull(Arr::get($raw, 'job_id')),
            status: self::stringOrNull(Arr::get($raw, 'status')),
            message: self::stringOrNull(Arr::get($raw, 'message')),
            success: self::boolOrNull(Arr::get($raw, 'success')),
            results: self::platformResultsFrom(Arr::get($raw, 'results')),
            external_id: self::stringOrNull(Arr::get($raw, 'external_id')),
            total_platforms: self::intOrNull(Arr::get($raw, 'total_platforms')),
            scheduled_date: self::stringOrNull(Arr::get($raw, 'scheduled_date')),
            usage: is_array(Arr::get($raw, 'usage')) ? UsageData::fromArray(Arr::get($raw, 'usage')) : null,
            warnings: array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'warnings')), is_string(...))),
        );
    }
}
