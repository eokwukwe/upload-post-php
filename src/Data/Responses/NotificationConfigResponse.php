<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class NotificationConfigResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $notifications
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $notifications = [],
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
            self::arrayOrEmpty(Arr::get($raw, 'notifications')),
        );
    }
}
