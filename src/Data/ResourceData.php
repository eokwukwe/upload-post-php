<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class ResourceData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $id = null,
        public ?string $name = null,
        public ?string $title = null,
        public ?string $picture = null,
        public ?string $account_id = null,
        public ?string $account_name = null,
        public ?string $vanity_name = null,
        public ?int $followers = null,
        public ?int $likes = null,
        public ?string $profile = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'id') ?? Arr::get($raw, 'page_id') ?? Arr::get($raw, 'name')),
            self::stringOrNull(Arr::get($raw, 'name') ?? Arr::get($raw, 'page_name')),
            self::stringOrNull(Arr::get($raw, 'title')),
            self::stringOrNull(Arr::get($raw, 'picture')),
            self::stringOrNull(Arr::get($raw, 'account_id')),
            self::stringOrNull(Arr::get($raw, 'account_name')),
            self::stringOrNull(Arr::get($raw, 'vanityName') ?? Arr::get($raw, 'vanity_name')),
            self::intOrNull(Arr::get($raw, 'followers')),
            self::intOrNull(Arr::get($raw, 'likes')),
            self::stringOrNull(Arr::get($raw, 'profile')),
        );
    }
}
