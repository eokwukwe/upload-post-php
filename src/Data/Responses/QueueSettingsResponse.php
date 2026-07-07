<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSettingsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $queue_settings
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $queue_settings = [],
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
            self::settingsOrEmpty(Arr::get($raw, 'queue_settings')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function settingsOrEmpty(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $settings = [];

        foreach ($value as $key => $setting) {
            if (is_string($key)) {
                $settings[$key] = $setting;
            }
        }

        return $settings;
    }
}
