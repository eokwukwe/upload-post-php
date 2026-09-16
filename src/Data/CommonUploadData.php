<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeInterface;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class CommonUploadData
{
    use InteractsWithData;

    /**
     * Common Upload-Post API fields.
     *
     * @param  list<Platform|string>  $platforms
     * @param  list<string|object>  $first_comment_media
     */
    public function __construct(
        public string $user,
        public array $platforms,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $first_comment = null,
        public DateTimeInterface|string|null $scheduled_date = null,
        public ?string $timezone = null,
        public ?bool $add_to_queue = null,
        public ?int $max_posts_per_slot = null,
        public ?bool $async_upload = null,
        public ?string $request_id = null,
        public ?string $external_id = null,
        public ?bool $is_ai_generated = null,
        public array $first_comment_media = [],
        public ?string $bluesky_title = null,
        public ?string $instagram_title = null,
        public ?string $facebook_title = null,
        public ?string $tiktok_title = null,
        public ?string $linkedin_title = null,
        public ?string $x_title = null,
        public ?string $youtube_title = null,
        public ?string $pinterest_title = null,
        public ?string $threads_title = null,
        public ?string $reddit_title = null,
        public ?string $discord_title = null,
        public ?string $telegram_title = null,
        public ?string $linkedin_description = null,
        public ?string $youtube_description = null,
        public ?string $facebook_description = null,
        public ?string $tiktok_description = null,
        public ?string $pinterest_description = null,
        public ?string $instagram_first_comment = null,
        public ?string $facebook_first_comment = null,
        public ?string $tiktok_first_comment = null,
        public ?string $x_first_comment = null,
        public ?string $threads_first_comment = null,
        public ?string $youtube_first_comment = null,
        public ?string $reddit_first_comment = null,
        public ?string $bluesky_first_comment = null,
        public ?string $linkedin_first_comment = null,
    ) {
        if (trim($this->user) === '') {
            throw new InvalidArgumentException('user is required.');
        }

        if ($this->platforms === []) {
            throw new InvalidArgumentException('At least one platform is required.');
        }

        self::validateScheduledDate($this->scheduled_date, $this->timezone);

        foreach (self::platformsToValues($this->platforms) as $platform) {
            if (trim($platform) === '') {
                throw new InvalidArgumentException('Platform values cannot be blank.');
            }
        }

        if ($this->hasPlatform(Platform::Reddit)) {
            throw new InvalidArgumentException('Reddit uploads are currently unavailable.');
        }

        if ($this->add_to_queue === true && $this->hasScheduledDate()) {
            throw new InvalidArgumentException('scheduled_date cannot be used with add_to_queue.');
        }

        if ($this->first_comment_media !== [] && ($this->hasScheduledDate() || $this->add_to_queue === true)) {
            throw new InvalidArgumentException('first_comment_media cannot be used with scheduled or queued uploads.');
        }

        if ($this->first_comment_media !== []) {
            throw new InvalidArgumentException('first_comment_media is unavailable while Reddit uploads are unavailable.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            platforms: self::platformListFrom($data['platforms'] ?? $data['platform'] ?? $data['platform[]'] ?? []),
            title: self::stringOrNull($data['title'] ?? null),
            description: self::stringOrNull($data['description'] ?? null),
            first_comment: self::stringOrNull($data['first_comment'] ?? null),
            scheduled_date: self::dateFrom($data['scheduled_date'] ?? null),
            timezone: self::stringOrNull($data['timezone'] ?? null),
            add_to_queue: self::boolOrNull($data['add_to_queue'] ?? null),
            max_posts_per_slot: self::intOrNull($data['max_posts_per_slot'] ?? null),
            async_upload: self::boolOrNull($data['async_upload'] ?? null),
            request_id: self::stringOrNull($data['request_id'] ?? null),
            external_id: self::stringOrNull($data['external_id'] ?? null),
            is_ai_generated: self::boolOrNull($data['is_ai_generated'] ?? null),
            first_comment_media: self::mediaListFrom($data['first_comment_media'] ?? []),
            bluesky_title: self::stringOrNull($data['bluesky_title'] ?? null),
            instagram_title: self::stringOrNull($data['instagram_title'] ?? null),
            facebook_title: self::stringOrNull($data['facebook_title'] ?? null),
            tiktok_title: self::stringOrNull($data['tiktok_title'] ?? null),
            linkedin_title: self::stringOrNull($data['linkedin_title'] ?? null),
            x_title: self::stringOrNull($data['x_title'] ?? null),
            youtube_title: self::stringOrNull($data['youtube_title'] ?? null),
            pinterest_title: self::stringOrNull($data['pinterest_title'] ?? null),
            threads_title: self::stringOrNull($data['threads_title'] ?? null),
            reddit_title: self::stringOrNull($data['reddit_title'] ?? null),
            discord_title: self::stringOrNull($data['discord_title'] ?? null),
            telegram_title: self::stringOrNull($data['telegram_title'] ?? null),
            linkedin_description: self::stringOrNull($data['linkedin_description'] ?? null),
            youtube_description: self::stringOrNull($data['youtube_description'] ?? null),
            facebook_description: self::stringOrNull($data['facebook_description'] ?? null),
            tiktok_description: self::stringOrNull($data['tiktok_description'] ?? null),
            pinterest_description: self::stringOrNull($data['pinterest_description'] ?? null),
            instagram_first_comment: self::stringOrNull($data['instagram_first_comment'] ?? null),
            facebook_first_comment: self::stringOrNull($data['facebook_first_comment'] ?? null),
            tiktok_first_comment: self::stringOrNull($data['tiktok_first_comment'] ?? null),
            x_first_comment: self::stringOrNull($data['x_first_comment'] ?? null),
            threads_first_comment: self::stringOrNull($data['threads_first_comment'] ?? null),
            youtube_first_comment: self::stringOrNull($data['youtube_first_comment'] ?? null),
            reddit_first_comment: self::stringOrNull($data['reddit_first_comment'] ?? null),
            bluesky_first_comment: self::stringOrNull($data['bluesky_first_comment'] ?? null),
            linkedin_first_comment: self::stringOrNull($data['linkedin_first_comment'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'user' => $this->user,
            'platforms' => self::platformsToValues($this->platforms),
            'title' => $this->title,
            'description' => $this->description,
            'first_comment' => $this->first_comment,
            'scheduled_date' => self::date($this->scheduled_date),
            'timezone' => $this->timezone,
            'add_to_queue' => $this->add_to_queue,
            'max_posts_per_slot' => $this->max_posts_per_slot,
            'async_upload' => $this->async_upload,
            'request_id' => $this->request_id,
            'external_id' => $this->external_id,
            'is_ai_generated' => $this->is_ai_generated,
            'first_comment_media' => $this->first_comment_media,
            'bluesky_title' => $this->bluesky_title,
            'instagram_title' => $this->instagram_title,
            'facebook_title' => $this->facebook_title,
            'tiktok_title' => $this->tiktok_title,
            'linkedin_title' => $this->linkedin_title,
            'x_title' => $this->x_title,
            'youtube_title' => $this->youtube_title,
            'pinterest_title' => $this->pinterest_title,
            'threads_title' => $this->threads_title,
            'reddit_title' => $this->reddit_title,
            'discord_title' => $this->discord_title,
            'telegram_title' => $this->telegram_title,
            'linkedin_description' => $this->linkedin_description,
            'youtube_description' => $this->youtube_description,
            'facebook_description' => $this->facebook_description,
            'tiktok_description' => $this->tiktok_description,
            'pinterest_description' => $this->pinterest_description,
            'instagram_first_comment' => $this->instagram_first_comment,
            'facebook_first_comment' => $this->facebook_first_comment,
            'tiktok_first_comment' => $this->tiktok_first_comment,
            'x_first_comment' => $this->x_first_comment,
            'threads_first_comment' => $this->threads_first_comment,
            'youtube_first_comment' => $this->youtube_first_comment,
            'reddit_first_comment' => $this->reddit_first_comment,
            'bluesky_first_comment' => $this->bluesky_first_comment,
            'linkedin_first_comment' => $this->linkedin_first_comment,
        ]);
    }

    public function addForVideoTo(MultipartPayload $payload): MultipartPayload
    {
        $this->addBaseTo($payload, [
            Platform::Instagram,
            Platform::Facebook,
            Platform::Threads,
            Platform::Bluesky,
            Platform::X,
            Platform::YouTube,
            Platform::LinkedIn,
            Platform::TikTok,
        ]);

        $this->fieldForPlatform($payload, Platform::TikTok, 'tiktok_title', $this->tiktok_title);
        $this->fieldForPlatform($payload, Platform::Instagram, 'instagram_title', $this->instagram_title);
        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_title', $this->linkedin_title);
        $this->fieldForPlatform($payload, Platform::YouTube, 'youtube_title', $this->youtube_title);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_title', $this->facebook_title);
        $this->fieldForPlatform($payload, Platform::X, 'x_title', $this->x_title);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_title', $this->threads_title);
        $this->fieldForPlatform($payload, Platform::Pinterest, 'pinterest_title', $this->pinterest_title);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_title', $this->bluesky_title);
        $this->fieldForPlatform($payload, Platform::Discord, 'discord_title', $this->discord_title);
        $this->fieldForPlatform($payload, Platform::Telegram, 'telegram_title', $this->telegram_title);

        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_description', $this->linkedin_description);
        $this->fieldForPlatform($payload, Platform::YouTube, 'youtube_description', $this->youtube_description);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_description', $this->facebook_description);
        $this->fieldForPlatform($payload, Platform::Pinterest, 'pinterest_description', $this->pinterest_description);

        $this->fieldForPlatform($payload, Platform::Instagram, 'instagram_first_comment', $this->instagram_first_comment);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_first_comment', $this->facebook_first_comment);
        $this->fieldForPlatform($payload, Platform::TikTok, 'tiktok_first_comment', $this->tiktok_first_comment);
        $this->fieldForPlatform($payload, Platform::X, 'x_first_comment', $this->x_first_comment);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_first_comment', $this->threads_first_comment);
        $this->fieldForPlatform($payload, Platform::YouTube, 'youtube_first_comment', $this->youtube_first_comment);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_first_comment', $this->bluesky_first_comment);
        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_first_comment', $this->linkedin_first_comment);
        $payload->field('is_ai_generated', $this->is_ai_generated);

        return $payload;
    }

    public function addForPhotosTo(MultipartPayload $payload): MultipartPayload
    {
        $this->addBaseTo($payload, [
            Platform::Instagram,
            Platform::Facebook,
            Platform::Threads,
            Platform::Bluesky,
            Platform::X,
            Platform::LinkedIn,
            Platform::TikTok,
        ]);

        $this->fieldForPlatform($payload, Platform::TikTok, 'tiktok_title', $this->tiktok_title);
        $this->fieldForPlatform($payload, Platform::Instagram, 'instagram_title', $this->instagram_title);
        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_title', $this->linkedin_title);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_title', $this->facebook_title);
        $this->fieldForPlatform($payload, Platform::X, 'x_title', $this->x_title);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_title', $this->threads_title);
        $this->fieldForPlatform($payload, Platform::Pinterest, 'pinterest_title', $this->pinterest_title);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_title', $this->bluesky_title);
        $this->fieldForPlatform($payload, Platform::Discord, 'discord_title', $this->discord_title);
        $this->fieldForPlatform($payload, Platform::Telegram, 'telegram_title', $this->telegram_title);

        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_description', $this->linkedin_description);
        $this->fieldForPlatform($payload, Platform::TikTok, 'tiktok_description', $this->tiktok_description);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_description', $this->facebook_description);
        $this->fieldForPlatform($payload, Platform::Pinterest, 'pinterest_description', $this->pinterest_description);

        $this->fieldForPlatform($payload, Platform::Instagram, 'instagram_first_comment', $this->instagram_first_comment);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_first_comment', $this->facebook_first_comment);
        $this->fieldForPlatform($payload, Platform::TikTok, 'tiktok_first_comment', $this->tiktok_first_comment);
        $this->fieldForPlatform($payload, Platform::X, 'x_first_comment', $this->x_first_comment);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_first_comment', $this->threads_first_comment);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_first_comment', $this->bluesky_first_comment);
        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_first_comment', $this->linkedin_first_comment);
        $payload->field('is_ai_generated', $this->is_ai_generated);

        return $payload;
    }

    public function addForTextTo(MultipartPayload $payload): MultipartPayload
    {
        $this->addBaseTo($payload, [
            Platform::Facebook,
            Platform::Threads,
            Platform::Bluesky,
            Platform::X,
            Platform::LinkedIn,
        ]);

        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_title', $this->linkedin_title);
        $this->fieldForPlatform($payload, Platform::X, 'x_title', $this->x_title);
        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_title', $this->facebook_title);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_title', $this->threads_title);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_title', $this->bluesky_title);
        $this->fieldForPlatform($payload, Platform::Discord, 'discord_title', $this->discord_title);
        $this->fieldForPlatform($payload, Platform::Telegram, 'telegram_title', $this->telegram_title);

        $this->fieldForPlatform($payload, Platform::Facebook, 'facebook_first_comment', $this->facebook_first_comment);
        $this->fieldForPlatform($payload, Platform::X, 'x_first_comment', $this->x_first_comment);
        $this->fieldForPlatform($payload, Platform::Threads, 'threads_first_comment', $this->threads_first_comment);
        $this->fieldForPlatform($payload, Platform::Bluesky, 'bluesky_first_comment', $this->bluesky_first_comment);
        $this->fieldForPlatform($payload, Platform::LinkedIn, 'linkedin_first_comment', $this->linkedin_first_comment);

        return $payload;
    }

    /**
     * @param  list<Platform>  $firstCommentPlatforms
     */
    private function addBaseTo(MultipartPayload $payload, array $firstCommentPlatforms): void
    {
        $payload
            ->field('user', $this->user)
            ->field('title', $this->title)
            ->field('platform[]', self::platformsToValues($this->platforms))
            ->field('scheduled_date', self::date($this->scheduled_date))
            ->field('timezone', $this->timezone)
            ->field('add_to_queue', $this->add_to_queue)
            ->field('max_posts_per_slot', $this->max_posts_per_slot)
            ->field('async_upload', $this->async_upload)
            ->field('request_id', $this->request_id)
            ->field('external_id', $this->external_id)
            ->field('description', $this->description);

        if ($this->hasAnyPlatform($firstCommentPlatforms)) {
            $payload->field('first_comment', $this->first_comment);
        }
    }

    private function fieldForPlatform(
        MultipartPayload $payload,
        Platform $platform,
        string $field,
        mixed $value
    ): void {
        if ($this->hasPlatform($platform)) {
            $payload->field($field, $value);
        }
    }

    private function hasPlatform(Platform $platform): bool
    {
        return in_array($platform->value, self::platformsToValues($this->platforms), true);
    }

    /**
     * @param  list<Platform>  $platforms
     */
    private function hasAnyPlatform(array $platforms): bool
    {
        foreach ($platforms as $platform) {
            if ($this->hasPlatform($platform)) {
                return true;
            }
        }

        return false;
    }

    private function hasScheduledDate(): bool
    {
        return $this->scheduled_date instanceof DateTimeInterface
            || (is_string($this->scheduled_date) && trim($this->scheduled_date) !== '');
    }
}
