<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class LinkedinPagesResponse extends ResourceListResponse
{
    public function __construct(
        array $raw,
        ?bool $success = null,
        array $items = [],
        public ?string $selected_page_id = null,
        public ?string $selected_page_name = null,
    ) {
        parent::__construct($raw, $success, $items);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'pages') {
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
            self::resourcesFrom(Arr::get($raw, 'pages') ?? Arr::get($raw, $itemsKey) ?? Arr::get($raw, 'items')),
            self::stringOrNull(Arr::get($raw, 'selected_page_id')),
            self::stringOrNull(Arr::get($raw, 'selected_page_name')),
        );
    }
}
