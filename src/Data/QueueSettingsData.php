<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSettingsData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<QueueSettingsSlotData>  $slots
     * @param  list<int>  $days_of_week
     * @param  list<string>  $full_slots
     */
    public function __construct(
        array $raw,
        public ?string $timezone = null,
        public array $slots = [],
        public array $days_of_week = [],
        public ?int $max_posts_per_slot = null,
        public array $full_slots = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $slots = Arr::get($raw, 'slots');
        $days = Arr::get($raw, 'days_of_week');
        $fullSlots = Arr::get($raw, 'full_slots');
        $slotData = [];
        $dayData = [];
        $fullSlotData = [];

        if (is_array($slots)) {
            foreach ($slots as $slot) {
                $slotData[] = QueueSettingsSlotData::fromArray(is_array($slot) ? $slot : []);
            }
        }

        if (is_array($days)) {
            foreach ($days as $day) {
                $dayValue = self::intOrNull($day);

                if ($dayValue !== null) {
                    $dayData[] = $dayValue;
                }
            }
        }

        if (is_array($fullSlots)) {
            foreach ($fullSlots as $slot) {
                $slotValue = self::stringOrNull($slot);

                if ($slotValue !== null) {
                    $fullSlotData[] = $slotValue;
                }
            }
        }

        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'timezone')),
            $slotData,
            $dayData,
            self::intOrNull(Arr::get($raw, 'max_posts_per_slot')),
            $fullSlotData,
        );
    }
}
