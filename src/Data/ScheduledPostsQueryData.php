<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;

final readonly class ScheduledPostsQueryData
{
    use InteractsWithData;

    public function __construct(
        public ?string $profile_username = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?int $limit = null,
        public int $offset = 0,
    ) {
        if ($this->limit !== null && $this->limit < 1) {
            throw new InvalidArgumentException('limit must be at least 1.');
        }

        if ($this->offset < 0) {
            throw new InvalidArgumentException('offset cannot be negative.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            profile_username: self::stringOrNull($data['profile_username'] ?? null),
            from: self::stringOrNull($data['from'] ?? null),
            to: self::stringOrNull($data['to'] ?? null),
            limit: self::intOrNull($data['limit'] ?? null),
            offset: self::intOrNull($data['offset'] ?? null) ?? 0,
        );
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return array_filter([
            'profile_username' => $this->profile_username,
            'from' => $this->from,
            'to' => $this->to,
            'limit' => $this->limit === null ? null : (string) $this->limit,
            'offset' => $this->offset === 0 ? null : (string) $this->offset,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }
}
