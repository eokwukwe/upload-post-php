<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class PlatformMetricsData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $available_metrics
     * @param  array<string, string>  $metric_labels
     */
    public function __construct(
        array $raw,
        public ?string $primary_impressions_field = null,
        public array $available_metrics = [],
        public array $metric_labels = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $availableMetrics = array_values(array_filter(
            self::arrayOrEmpty(Arr::get($raw, 'available_metrics')),
            is_string(...),
        ));
        $labels = [];

        foreach (self::arrayOrEmpty(Arr::get($raw, 'metric_labels')) as $key => $label) {
            if (is_string($key) && is_string($label)) {
                $labels[$key] = $label;
            }
        }

        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'primary_impressions_field')),
            $availableMetrics,
            $labels,
        );
    }
}
