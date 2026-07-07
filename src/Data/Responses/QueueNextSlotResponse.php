<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueNextSlotResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>|null  $next_slot
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?array $next_slot = null,
        public ?string $message = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $nextSlot = Arr::get($raw, 'next_slot');

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array($nextSlot) ? $nextSlot : null,
            self::stringOrNull(Arr::get($raw, 'message')),
        );
    }
}
