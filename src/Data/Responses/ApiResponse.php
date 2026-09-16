<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\CommentData;
use Softgeng\UploadPost\Data\HistoryItemData;
use Softgeng\UploadPost\Data\MediaData;
use Softgeng\UploadPost\Data\PlatformUploadResult;
use Softgeng\UploadPost\Data\QueueSlotData;
use Softgeng\UploadPost\Data\ResourceData;
use Softgeng\UploadPost\Data\ResponseData;
use Softgeng\UploadPost\Data\ResponseItem;
use Softgeng\UploadPost\Data\ScheduledPostData;
use Softgeng\UploadPost\Data\UserProfileData;

abstract readonly class ApiResponse extends ResponseData
{
    /**
     * @return list<ResponseItem>
     */
    protected static function itemsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item): ResponseItem => ResponseItem::fromArray(is_array($item) ? $item : ['value' => $item]),
            $value,
        ));
    }

    /** @return list<CommentData> */
    protected static function commentsFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): CommentData => CommentData::fromArray($item));
    }

    /** @return list<MediaData> */
    protected static function mediaFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): MediaData => MediaData::fromArray($item));
    }

    /** @return list<HistoryItemData> */
    protected static function historyFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): HistoryItemData => HistoryItemData::fromArray($item));
    }

    /** @return list<ScheduledPostData> */
    protected static function scheduledPostsFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): ScheduledPostData => ScheduledPostData::fromArray($item));
    }

    /** @return list<UserProfileData> */
    protected static function userProfilesFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): UserProfileData => UserProfileData::fromArray($item));
    }

    /** @return list<ResourceData> */
    protected static function resourcesFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): ResourceData => ResourceData::fromArray($item));
    }

    /** @return list<QueueSlotData> */
    protected static function queueSlotsFrom(mixed $value): array
    {
        return self::mapList($value, static fn (array $item): QueueSlotData => QueueSlotData::fromArray($item));
    }

    /**
     * @return list<PlatformUploadResult>
     */
    protected static function platformResultsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $results = [];
        foreach ($value as $platform => $result) {
            $results[] = PlatformUploadResult::fromArray(is_array($result) ? $result : ['value' => $result], is_string($platform) ? $platform : null);
        }

        return $results;
    }

    /**
     * @template T of ResponseData
     *
     * @param  callable(array<string, mixed>): T  $mapper
     * @return list<T>
     */
    private static function mapList(mixed $value, callable $mapper): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item) => $mapper(is_array($item) ? $item : []),
            $value,
        ));
    }
}
