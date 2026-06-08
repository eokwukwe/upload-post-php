<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeInterface;
use InvalidArgumentException;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class CommonUploadData
{
    use Concerns;

    /**
     * Common Upload-Post API fields.
     *
     * @param  list<Platform|string>  $platforms
     * @param  list<Media|string|object>  $first_comment_media
     */
    public function __construct(
        public string $user,
        public array $platforms,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $first_comment = null,
        public ?string $alt_text = null,
        public DateTimeInterface|string|null $scheduled_date = null,
        public ?string $timezone = null,
        public ?bool $add_to_queue = null,
        public ?int $max_posts_per_slot = null,
        public ?bool $async_upload = null,
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
        public ?string $linkedin_description = null,
        public ?string $youtube_description = null,
        public ?string $facebook_description = null,
        public ?string $tiktok_description = null,
        public ?string $pinterest_description = null,
        public ?string $instagram_first_comment = null,
        public ?string $facebook_first_comment = null,
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
            alt_text: self::stringOrNull($data['alt_text'] ?? null),
            scheduled_date: self::dateFrom($data['scheduled_date'] ?? null),
            timezone: self::stringOrNull($data['timezone'] ?? null),
            add_to_queue: self::boolOrNull($data['add_to_queue'] ?? null),
            max_posts_per_slot: self::intOrNull($data['max_posts_per_slot'] ?? null),
            async_upload: self::boolOrNull($data['async_upload'] ?? null),
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
            linkedin_description: self::stringOrNull($data['linkedin_description'] ?? null),
            youtube_description: self::stringOrNull($data['youtube_description'] ?? null),
            facebook_description: self::stringOrNull($data['facebook_description'] ?? null),
            tiktok_description: self::stringOrNull($data['tiktok_description'] ?? null),
            pinterest_description: self::stringOrNull($data['pinterest_description'] ?? null),
            instagram_first_comment: self::stringOrNull($data['instagram_first_comment'] ?? null),
            facebook_first_comment: self::stringOrNull($data['facebook_first_comment'] ?? null),
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
            'alt_text' => $this->alt_text,
            'scheduled_date' => self::date($this->scheduled_date),
            'timezone' => $this->timezone,
            'add_to_queue' => $this->add_to_queue,
            'max_posts_per_slot' => $this->max_posts_per_slot,
            'async_upload' => $this->async_upload,
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
            'linkedin_description' => $this->linkedin_description,
            'youtube_description' => $this->youtube_description,
            'facebook_description' => $this->facebook_description,
            'tiktok_description' => $this->tiktok_description,
            'pinterest_description' => $this->pinterest_description,
            'instagram_first_comment' => $this->instagram_first_comment,
            'facebook_first_comment' => $this->facebook_first_comment,
            'x_first_comment' => $this->x_first_comment,
            'threads_first_comment' => $this->threads_first_comment,
            'youtube_first_comment' => $this->youtube_first_comment,
            'reddit_first_comment' => $this->reddit_first_comment,
            'bluesky_first_comment' => $this->bluesky_first_comment,
            'linkedin_first_comment' => $this->linkedin_first_comment,
        ]);
    }

    public function addCommonTo(MultipartPayload $payload): MultipartPayload
    {
        $payload
            ->field('user', $this->user)
            ->field('title', $this->title)
            ->field('platform[]', self::platformsToValues($this->platforms))
            ->field('first_comment', $this->first_comment)
            ->field('alt_text', $this->alt_text)
            ->field('scheduled_date', self::date($this->scheduled_date))
            ->field('timezone', $this->timezone)
            ->field('add_to_queue', $this->add_to_queue)
            ->field('max_posts_per_slot', $this->max_posts_per_slot)
            ->field('async_upload', $this->async_upload)
            ->field('description', $this->description)
            ->field('bluesky_title', $this->bluesky_title)
            ->field('instagram_title', $this->instagram_title)
            ->field('facebook_title', $this->facebook_title)
            ->field('tiktok_title', $this->tiktok_title)
            ->field('linkedin_title', $this->linkedin_title)
            ->field('x_title', $this->x_title)
            ->field('youtube_title', $this->youtube_title)
            ->field('pinterest_title', $this->pinterest_title)
            ->field('threads_title', $this->threads_title)
            ->field('linkedin_description', $this->linkedin_description)
            ->field('youtube_description', $this->youtube_description)
            ->field('facebook_description', $this->facebook_description)
            ->field('tiktok_description', $this->tiktok_description)
            ->field('pinterest_description', $this->pinterest_description)
            ->field('instagram_first_comment', $this->instagram_first_comment)
            ->field('facebook_first_comment', $this->facebook_first_comment)
            ->field('x_first_comment', $this->x_first_comment)
            ->field('threads_first_comment', $this->threads_first_comment)
            ->field('youtube_first_comment', $this->youtube_first_comment)
            ->field('reddit_first_comment', $this->reddit_first_comment)
            ->field('bluesky_first_comment', $this->bluesky_first_comment)
            ->field('linkedin_first_comment', $this->linkedin_first_comment);

        foreach ($this->first_comment_media as $media) {
            $payload->media('first_comment_media[]', $media instanceof Media ? $media : Media::from($media));
        }

        return $payload;
    }
}
