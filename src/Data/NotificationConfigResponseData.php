<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class NotificationConfigResponseData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, bool>  $webhook_events
     */
    public function __construct(
        array $raw,
        public ?bool $webhook = null,
        public ?bool $telegram = null,
        public ?string $webhook_url = null,
        public ?string $webhook_secret = null,
        public ?string $telegram_chat_id = null,
        public array $webhook_events = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $channels = Arr::get($raw, 'channels');
        $channels = is_array($channels) ? $channels : [];
        $events = Arr::get($raw, 'webhook_events');
        $eventData = [];

        if (is_array($events)) {
            foreach ($events as $event => $enabled) {
                if (is_string($event) && is_bool($enabled)) {
                    $eventData[$event] = $enabled;
                }
            }
        }

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'webhook') ?? Arr::get($channels, 'webhook')),
            self::boolOrNull(Arr::get($raw, 'telegram') ?? Arr::get($channels, 'telegram')),
            self::stringOrNull(Arr::get($raw, 'webhook_url')),
            self::stringOrNull(Arr::get($raw, 'webhook_secret')),
            self::stringOrNull(Arr::get($raw, 'telegram_chat_id')),
            $eventData,
        );
    }
}
