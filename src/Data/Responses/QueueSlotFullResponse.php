<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSlotFullResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $full_slots
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $message = null,
        public array $full_slots = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'message')),
            self::arrayOrEmpty(Arr::get($raw, 'full_slots')),
        );
    }
}
