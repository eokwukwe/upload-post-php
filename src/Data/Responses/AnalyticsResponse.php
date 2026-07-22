<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class AnalyticsResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $data
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $data = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $data = Arr::get($raw, 'data');

        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array($data) ? $data : $raw,
        );
    }
}
