<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class MediaResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $media
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $media = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * Backward-compatible alias for older ListResponse usage.
     *
     * @return array<int|string, mixed>|null
     */
    public function __get(string $name): mixed
    {
        if ($name === 'items') {
            return $this->media;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::arrayOrEmpty(Arr::get($raw, 'media') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
        );
    }
}
