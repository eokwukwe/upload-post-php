<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

readonly class ResourceListResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $items
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $items = [],
        public ?string $pinterest_account_used = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw, string $itemsKey = 'data'): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::arrayOrEmpty(Arr::get($raw, $itemsKey) ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::stringOrNull(Arr::get($raw, 'pinterest_account_used')),
        );
    }
}
