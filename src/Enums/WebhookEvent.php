<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum WebhookEvent: string
{
    case UploadCompleted = 'upload_completed';
    case SocialAccountConnected = 'social_account_connected';
    case SocialAccountDisconnected = 'social_account_disconnected';
    case SocialAccountReauthRequired = 'social_account_reauth_required';

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        $events = [];

        foreach (self::cases() as $event) {
            $events[$event->value] = true;
        }

        return $events;
    }

    public static function contains(string $event): bool
    {
        return self::tryFrom($event) instanceof self;
    }
}
