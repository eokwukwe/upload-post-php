<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class GoogleBusinessLocationsResponse extends ResourceListResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<\Softgeng\UploadPost\Data\ResourceData>  $items
     */
    public function __construct(
        array $raw,
        ?bool $success = null,
        array $items = [],
        ?string $pinterest_account_used = null,
        public ?string $selected_location_id = null,
        public ?string $selected_location_name = null,
    ) {
        parent::__construct($raw, $success, $items, $pinterest_account_used);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'locations') {
            return $this->items;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw, string $itemsKey = 'data'): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::resourcesFrom(Arr::get($raw, 'locations') ?? Arr::get($raw, $itemsKey) ?? Arr::get($raw, 'items')),
            self::stringOrNull(Arr::get($raw, 'pinterest_account_used')),
            self::stringOrNull(Arr::get($raw, 'selected_location_id')),
            self::stringOrNull(Arr::get($raw, 'selected_location_name')),
        );
    }
}
