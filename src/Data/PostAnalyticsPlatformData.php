<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class PostAnalyticsPlatformData extends ResponseData
{
    /**
     * @param  array<int|string, mixed>  $raw
     * @param  array<int|string, mixed>  $post_metrics
     * @param  array<int|string, mixed>  $profile_snapshot_at_post_date
     * @param  array<int|string, mixed>  $profile_snapshot_latest
     * @param  list<string>  $available_metrics
     * @param  array<string, string>  $metric_labels
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?string $platform_post_id = null,
        public ?string $post_url = null,
        public array $post_metrics = [],
        public ?string $post_metrics_source = null,
        public ?string $post_metrics_error = null,
        public array $profile_snapshot_at_post_date = [],
        public array $profile_snapshot_latest = [],
        public ?string $profile_snapshot_latest_date = null,
        public array $available_metrics = [],
        public array $metric_labels = [],
        public ?string $primary_impressions_field = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::stringOrNull(Arr::get($raw, 'platform_post_id')),
            self::stringOrNull(Arr::get($raw, 'post_url')),
            self::arrayOrEmpty(Arr::get($raw, 'post_metrics')),
            self::stringOrNull(Arr::get($raw, 'post_metrics_source')),
            self::stringOrNull(Arr::get($raw, 'post_metrics_error')),
            self::arrayOrEmpty(Arr::get($raw, 'profile_snapshot_at_post_date')),
            self::arrayOrEmpty(Arr::get($raw, 'profile_snapshot_latest')),
            self::stringOrNull(Arr::get($raw, 'profile_snapshot_latest_date')),
            array_values(array_filter(self::arrayOrEmpty(Arr::get($raw, 'available_metrics')), is_string(...))),
            self::stringMap(Arr::get($raw, 'metric_labels')),
            self::stringOrNull(Arr::get($raw, 'primary_impressions_field')),
        );
    }

    /** @return array<string, string> */
    private static function stringMap(mixed $value): array
    {
        $map = [];

        foreach (self::arrayOrEmpty($value) as $key => $item) {
            if (is_string($key) && is_string($item)) {
                $map[$key] = $item;
            }
        }

        return $map;
    }
}
