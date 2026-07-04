<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class PinterestBoardsResponse extends ResourceListResponse
{
    public function __get(string $name): mixed
    {
        if ($name === 'boards') {
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
            self::arrayOrEmpty(Arr::get($raw, 'boards') ?? Arr::get($raw, $itemsKey) ?? Arr::get($raw, 'items')),
            self::stringOrNull(Arr::get($raw, 'pinterest_account_used')),
        );
    }
}
