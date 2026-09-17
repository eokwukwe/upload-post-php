<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\TikTokMusicCatalogData;
use Softgeng\UploadPost\Data\TikTokMusicTrackData;
use Softgeng\UploadPost\Support\Arr;

final readonly class TikTokMusicResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<TikTokMusicTrackData>  $tracks
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $query = null,
        public ?string $genre = null,
        public ?string $country_code = null,
        public ?string $date_range = null,
        public ?int $limit = null,
        public ?int $total = null,
        public array $tracks = [],
        public ?TikTokMusicCatalogData $catalog = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $catalog = Arr::get($raw, 'catalog');

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'query')),
            self::stringOrNull(Arr::get($raw, 'genre')),
            self::stringOrNull(Arr::get($raw, 'country_code')),
            self::stringOrNull(Arr::get($raw, 'date_range')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::intOrNull(Arr::get($raw, 'total')),
            self::tracksFrom(Arr::get($raw, 'tracks')),
            is_array($catalog) ? TikTokMusicCatalogData::fromArray($catalog) : null,
        );
    }

    /** @return list<TikTokMusicTrackData> */
    private static function tracksFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $track): TikTokMusicTrackData => TikTokMusicTrackData::fromArray(is_array($track) ? $track : []),
            $value,
        ));
    }
}
