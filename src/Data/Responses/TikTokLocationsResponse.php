<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\TikTokLocationData;
use Softgeng\UploadPost\Support\Arr;

final readonly class TikTokLocationsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<TikTokLocationData>  $locations
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $query = null,
        public array $locations = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'query')),
            self::locationsFrom(Arr::get($raw, 'locations')),
        );
    }

    /** @return list<TikTokLocationData> */
    private static function locationsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $location): TikTokLocationData => TikTokLocationData::fromArray(is_array($location) ? $location : []),
            $value,
        ));
    }
}
