<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;

abstract readonly class CommentRequestData
{
    use InteractsWithData;

    /** @var list<Platform> */
    protected const LIST_PLATFORMS = [
        Platform::Instagram,
        Platform::Facebook,
        Platform::YouTube,
        Platform::LinkedIn,
        Platform::TikTok,
        Platform::X,
        Platform::Threads,
        Platform::Bluesky,
    ];

    /** @var list<Platform> */
    protected const DELETE_PLATFORMS = [
        Platform::Instagram,
        Platform::Facebook,
        Platform::YouTube,
        Platform::LinkedIn,
        Platform::TikTok,
        Platform::X,
        Platform::Bluesky,
    ];

    /** @var list<Platform> */
    protected const ACTION_PLATFORMS = [
        Platform::TikTok,
        Platform::Facebook,
        Platform::Instagram,
        Platform::YouTube,
        Platform::Threads,
    ];

    /**
     * @param  list<Platform>  $supportedPlatforms
     */
    protected static function commentPlatform(Platform|string $platform, array $supportedPlatforms, string $operation): string
    {
        $value = trim((string) self::enumValue($platform));

        if (! in_array($value, self::platformsToValues($supportedPlatforms), true)) {
            throw new InvalidArgumentException("{$value} is not supported for {$operation} comments.");
        }

        return $value;
    }

    protected static function commentPlatformFrom(mixed $value): Platform|string
    {
        return $value instanceof Platform ? $value : self::stringOrNull($value) ?? '';
    }

    protected static function requireCommentValue(?string $value, string $field): void
    {
        if ($value === null || trim($value) === '') {
            throw new InvalidArgumentException("{$field} is required.");
        }
    }

    protected static function hasCommentValue(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    /**
     * @param  list<?string>  $values
     */
    protected static function requireExactlyOneCommentTarget(array $values): void
    {
        $targets = array_filter($values, self::hasCommentValue(...));

        if (count($targets) !== 1) {
            throw new InvalidArgumentException('Exactly one of comment_id, post_id, or post_url is required.');
        }
    }
}
