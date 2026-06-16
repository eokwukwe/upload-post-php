<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\WebhookEvent;

final readonly class NotificationConfigData
{
    use Concerns;

    /** @var array<string, bool> */
    public array $webhook_events;

    /**
     * @param  array<int|string, mixed>  $webhook_events
     */
    public function __construct(
        public ?bool $webhook = null,
        public ?bool $telegram = null,
        public ?string $webhook_url = null,
        public ?string $telegram_chat_id = null,
        array $webhook_events = [],
    ) {
        $this->webhook_events = self::webhookEventsFrom($webhook_events);
    }

    /**
     * @param  array<int|string, mixed>  $events
     */
    public static function webhook(string $webhook_url, array $events = []): self
    {
        return new self(
            webhook: true,
            telegram: false,
            webhook_url: $webhook_url,
            webhook_events: self::webhookEventsFrom($events, defaultToAll: true),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $channels = is_array($data['channels'] ?? null) ? $data['channels'] : [];
        $webhook = self::boolOrNull($data['webhook'] ?? $channels['webhook'] ?? null);

        return new self(
            webhook: $webhook,
            telegram: self::boolOrNull($data['telegram'] ?? $channels['telegram'] ?? null),
            webhook_url: self::stringOrNull($data['webhook_url'] ?? null),
            telegram_chat_id: self::stringOrNull($data['telegram_chat_id'] ?? null),
            webhook_events: self::webhookEventsFrom(
                $data['webhook_events'] ?? [],
                defaultToAll: $webhook === true,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $channels = self::withoutBlankValues([
            'webhook' => $this->webhook,
            'telegram' => $this->telegram,
        ]);
        $webhookEvents = $this->webhook_events;

        if ($this->webhook === true) {
            $webhookEvents = array_replace(WebhookEvent::defaults(), $webhookEvents);
        }

        return self::withoutBlankValues([
            'channels' => $channels,
            'webhook_url' => $this->webhook_url,
            'telegram_chat_id' => $this->telegram_chat_id,
            'webhook_events' => $webhookEvents,
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private static function webhookEventsFrom(mixed $events, bool $defaultToAll = false): array
    {
        $webhookEvents = $defaultToAll ? WebhookEvent::defaults() : [];

        if (! is_array($events)) {
            return $webhookEvents;
        }

        foreach ($events as $event => $enabled) {
            if (is_int($event)) {
                if ($enabled instanceof WebhookEvent) {
                    $webhookEvents[$enabled->value] = true;

                    continue;
                }

                if (is_string($enabled) && WebhookEvent::contains($enabled)) {
                    $webhookEvents[$enabled] = true;

                    continue;
                }

                continue;
            }

            if (! WebhookEvent::contains($event)) {
                continue;
            }

            $value = self::boolOrNull($enabled);

            if ($value !== null) {
                $webhookEvents[$event] = $value;
            }
        }

        return $webhookEvents;
    }
}
