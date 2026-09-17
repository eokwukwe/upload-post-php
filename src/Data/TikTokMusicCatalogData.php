<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class TikTokMusicCatalogData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $genres_indexed
     */
    public function __construct(
        array $raw,
        public ?int $tracks_indexed = null,
        public array $genres_indexed = [],
        public ?bool $cached = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $genres = Arr::get($raw, 'genres_indexed');

        return new self(
            $raw,
            self::intOrNull(Arr::get($raw, 'tracks_indexed')),
            is_array($genres) ? array_values(array_filter(
                array_map(self::stringOrNull(...), $genres),
                static fn (?string $genre): bool => $genre !== null,
            )) : [],
            self::boolOrNull(Arr::get($raw, 'cached')),
        );
    }
}
