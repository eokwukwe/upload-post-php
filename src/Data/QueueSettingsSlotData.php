<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSettingsSlotData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?int $hour = null,
        public ?int $minute = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::intOrNull(Arr::get($raw, 'hour')),
            self::intOrNull(Arr::get($raw, 'minute')),
        );
    }
}
