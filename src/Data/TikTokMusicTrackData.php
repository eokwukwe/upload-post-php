<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class TikTokMusicTrackData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $genres
     */
    public function __construct(
        array $raw,
        public ?string $id = null,
        public ?string $commercial_music_id = null,
        public ?string $title = null,
        public ?string $artist = null,
        public ?int $duration = null,
        public ?int $rank = null,
        public array $genres = [],
        public ?string $cover_url = null,
        public ?string $preview_url = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'id')),
            self::stringOrNull(Arr::get($raw, 'commercial_music_id')),
            self::stringOrNull(Arr::get($raw, 'title')),
            self::stringOrNull(Arr::get($raw, 'artist')),
            self::intOrNull(Arr::get($raw, 'duration')),
            self::intOrNull(Arr::get($raw, 'rank')),
            self::genresFrom(Arr::get($raw, 'genres')),
            self::stringOrNull(Arr::get($raw, 'cover_url')),
            self::stringOrNull(Arr::get($raw, 'preview_url')),
        );
    }

    /** @return list<string> */
    private static function genresFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(self::stringOrNull(...), $value),
            static fn (?string $genre): bool => $genre !== null,
        ));
    }
}
