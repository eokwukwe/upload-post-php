<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueuePreviewResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $slots
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $timezone = null,
        public ?int $max_posts_per_slot = null,
        public array $slots = [],
        public ?string $next_available = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * Backward-compatible alias for list-style response usage.
     *
     * @return array<int|string, mixed>|null
     */
    public function __get(string $name): mixed
    {
        if ($name === 'items') {
            return $this->slots;
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
            self::stringOrNull(Arr::get($raw, 'timezone')),
            self::intOrNull(Arr::get($raw, 'max_posts_per_slot')),
            self::arrayOrEmpty(Arr::get($raw, 'slots') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::stringOrNull(Arr::get($raw, 'next_available')),
        );
    }
}
