<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\QueueSlotData;
use Softgeng\UploadPost\Support\Arr;

final readonly class QueueNextSlotResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?QueueSlotData $next_slot = null,
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
            is_array($nextSlot) ? QueueSlotData::fromArray($nextSlot) : null,
            self::stringOrNull(Arr::get($raw, 'message')),
        );
    }
}
