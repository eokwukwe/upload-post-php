<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class ListResponse extends ApiResponse
{
    /**
     * @param  array<int|string, mixed>  $raw
     * @param  array<int|string, mixed>  $items
     */
    public function __construct(array $raw, public array $items = [])
    {
        parent::__construct($raw);
    }

    /**
     * @param  array<int|string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $items = Arr::get($raw, 'data') ?? Arr::get($raw, 'items') ?? (array_is_list($raw) ? $raw : []);

        return new self($raw, is_array($items) ? $items : []);
    }
}
