<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\NotificationConfigResponseData;
use Softgeng\UploadPost\Support\Arr;

final readonly class NotificationConfigResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?NotificationConfigResponseData $notifications = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $notifications = Arr::get($raw, 'notifications');

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array($notifications)
                ? NotificationConfigResponseData::fromArray($notifications)
                : null,
        );
    }
}
