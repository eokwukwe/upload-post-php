<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\PlatformMetricsData;

final readonly class PlatformMetricsResponse extends ApiResponse
{
    /**
     * @param  array<array-key, mixed>  $raw
     * @param  array<string, PlatformMetricsData>  $platforms
     */
    public function __construct(array $raw, public array $platforms = [])
    {
        parent::__construct($raw);
    }

    /** @param array<array-key, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $platforms = [];

        foreach ($raw as $platform => $data) {
            if (is_string($platform) && is_array($data)) {
                $platforms[$platform] = PlatformMetricsData::fromArray($data);
            }
        }

        return new self($raw, $platforms);
    }
}
