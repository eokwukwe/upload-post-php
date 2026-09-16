<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\QueueSettingsData;
use Softgeng\UploadPost\Support\Arr;

final readonly class QueueSettingsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?QueueSettingsData $queue_settings = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $settings = Arr::get($raw, 'queue_settings');

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array($settings)
                ? QueueSettingsData::fromArray($settings)
                : null,
        );
    }
}
