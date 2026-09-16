<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Concerns;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;

trait InteractsWithData
{
    protected static function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * @param  list<Platform|string>  $platforms
     * @return list<string>
     */
    protected static function platformsToValues(array $platforms): array
    {
        return array_map(
            static fn (Platform|string $p): string => trim($p instanceof Platform ? $p->value : $p),
            $platforms,
        );
    }

    protected static function date(DateTimeInterface|string|null $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format(DateTimeInterface::ATOM) : $date;
    }

    protected static function validateScheduledDate(
        DateTimeInterface|string|null $date,
        ?string $timezone = null,
        bool $enforceMaximumHorizon = true,
    ): void {
        if ($date === null || (is_string($date) && trim($date) === '')) {
            return;
        }

        try {
            if ($date instanceof DateTimeInterface) {
                $scheduled = DateTimeImmutable::createFromInterface($date);
            } else {
                if (! self::isIso8601Date($date)) {
                    throw new InvalidArgumentException('scheduled_date must be a valid ISO 8601 date.');
                }

                $zone = $timezone !== null && trim($timezone) !== ''
                    ? new DateTimeZone($timezone)
                    : null;
                $scheduled = new DateTimeImmutable($date, $zone);
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException('scheduled_date must be a valid ISO 8601 date.', $e->getCode(), previous: $e);
        }

        $now = new DateTimeImmutable('now');

        if ($scheduled <= $now) {
            throw new InvalidArgumentException('scheduled_date must be in the future.');
        }

        if ($enforceMaximumHorizon && $scheduled > $now->modify('+365 days')) {
            throw new InvalidArgumentException('scheduled_date cannot be more than 365 days in the future.');
        }
    }

    protected static function isIso8601Date(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}(?:[Tt ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:[Zz]|[+-]\d{2}:?\d{2})?)?$/D', trim($value)) === 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function withoutBlankValues(array $data): array
    {
        return array_filter(
            $data,
            static fn (mixed $value): bool => ! in_array($value, [null, '', []], true)
        );
    }

    /**
     * @return list<mixed>
     */
    protected static function listFrom(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? array_values($value) : [$value];
    }

    /**
     * @return list<Platform|string>
     */
    protected static function platformListFrom(mixed $value): array
    {
        $platforms = [];

        foreach (self::listFrom($value) as $platform) {
            if ($platform instanceof Platform || is_string($platform)) {
                $platforms[] = $platform;
            } elseif (is_scalar($platform)) {
                $platforms[] = (string) $platform;
            }
        }

        return $platforms;
    }

    /**
     * @return list<string>
     */
    protected static function stringListFrom(mixed $value): array
    {
        $items = [];

        foreach (self::listFrom($value) as $item) {
            if (is_scalar($item) && (string) $item !== '') {
                $items[] = (string) $item;
            }
        }

        return $items;
    }

    /**
     * @return list<string|object>
     */
    protected static function mediaListFrom(mixed $value): array
    {
        $media = [];

        foreach (self::listFrom($value) as $item) {
            if (is_string($item) || is_object($item)) {
                $media[] = $item;
            }
        }

        return $media;
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    protected static function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    protected static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected static function intStringOrNull(mixed $value): int|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_int($value) || is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);
    }

    protected static function dateFrom(mixed $value): DateTimeInterface|string|null
    {
        if ($value instanceof DateTimeInterface || is_string($value) || $value === null) {
            return $value;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    protected static function mediaInputFrom(mixed $value): string|object
    {
        return is_string($value) || is_object($value) ? $value : '';
    }

    protected static function mediaInputOrNull(mixed $value): string|object|null
    {
        return is_string($value) || is_object($value) ? $value : null;
    }

    protected static function mediaIsUrl(mixed $value): bool
    {
        return (is_string($value) && preg_match('/^https?:\/\//i', $value) === 1)
            || ($value instanceof Media && $value->isUrl());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function commonFrom(array $data): CommonUploadData
    {
        $common = $data['common'] ?? null;

        if ($common instanceof CommonUploadData) {
            return $common;
        }

        return CommonUploadData::fromArray(is_array($common) ? $common : $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function optionsFrom(array $data): PlatformOptions
    {
        $options = $data['options'] ?? null;

        if ($options instanceof PlatformOptions) {
            return $options;
        }

        return PlatformOptions::fromArray(is_array($options) ? $options : $data);
    }
}
