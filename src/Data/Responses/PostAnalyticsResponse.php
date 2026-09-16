<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\PostAnalyticsPlatformData;
use Softgeng\UploadPost\Data\PostAnalyticsPostData;
use Softgeng\UploadPost\Support\Arr;

final readonly class PostAnalyticsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, PostAnalyticsPlatformData>  $platforms
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?PostAnalyticsPostData $post = null,
        public array $platforms = [],
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $post = Arr::get($raw, 'post');
        $platforms = [];

        $platformValues = Arr::get($raw, 'platforms');

        if (is_array($platformValues)) {
            foreach ($platformValues as $platform => $data) {
                if (is_string($platform) && is_array($data)) {
                    $platforms[$platform] = PostAnalyticsPlatformData::fromArray($data);
                }
            }
        }

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array($post) ? PostAnalyticsPostData::fromArray($post) : null,
            $platforms,
        );
    }
}
