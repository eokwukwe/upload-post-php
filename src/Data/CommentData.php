<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class CommentData extends ResponseData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        array $raw,
        public ?string $id = null,
        public ?string $text = null,
        public ?string $timestamp = null,
        public ?string $user_id = null,
        public ?string $username = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'id')),
            self::stringOrNull(Arr::get($raw, 'text')),
            self::stringOrNull(Arr::get($raw, 'timestamp')),
            self::stringOrNull(Arr::get($raw, 'user.id')),
            self::stringOrNull(Arr::get($raw, 'user.username')),
        );
    }
}
