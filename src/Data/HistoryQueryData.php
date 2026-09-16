<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;

final readonly class HistoryQueryData
{
    use InteractsWithData;

    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public Platform|string|null $platform = null,
        public ?string $status = null,
        public ?string $profile_username = null,
        public ?string $request_id = null,
        public ?string $job_id = null,
        public ?string $external_id = null,
        public ?string $start = null,
        public ?string $end = null,
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('page must be at least 1.');
        }

        if ($this->limit < 1) {
            throw new InvalidArgumentException('limit must be at least 1.');
        }

        if (! in_array($this->limit, [10, 20, 50, 100], true)) {
            throw new InvalidArgumentException('limit must be one of 10, 20, 50, or 100.');
        }

        foreach (['request_id' => $this->request_id, 'job_id' => $this->job_id, 'external_id' => $this->external_id] as $field => $value) {
            if ($value !== null && (strlen($value) > 200 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1)) {
                throw new InvalidArgumentException("{$field} must be 200 characters or fewer and contain no control characters.");
            }
        }

        $hasStart = $this->start !== null && trim($this->start) !== '';
        $hasEnd = $this->end !== null && trim($this->end) !== '';

        if ($hasStart !== $hasEnd) {
            throw new InvalidArgumentException('start and end must be provided together.');
        }

        if ($hasStart && $hasEnd) {
            try {
                if (! self::isIso8601Date((string) $this->start) || ! self::isIso8601Date((string) $this->end)) {
                    throw new InvalidArgumentException('start and end must be valid dates.');
                }

                $start = new DateTimeImmutable((string) $this->start);
                $end = new DateTimeImmutable((string) $this->end);
            } catch (Exception $e) {
                throw new InvalidArgumentException('start and end must be valid dates.', $e->getCode(), previous: $e);
            }

            if ($start > $end) {
                throw new InvalidArgumentException('start must be before or equal to end.');
            }

            if ($end > $start->modify('+2 months')) {
                throw new InvalidArgumentException('The history date range cannot exceed 2 months.');
            }
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            page: self::intOrNull($data['page'] ?? null) ?? 1,
            limit: self::intOrNull($data['limit'] ?? null) ?? 10,
            platform: self::stringOrNull($data['platform'] ?? null),
            status: self::stringOrNull($data['status'] ?? null),
            profile_username: self::stringOrNull($data['profile_username'] ?? $data['profile'] ?? null),
            request_id: self::stringOrNull($data['request_id'] ?? null),
            job_id: self::stringOrNull($data['job_id'] ?? null),
            external_id: self::stringOrNull($data['external_id'] ?? null),
            start: self::stringOrNull($data['start'] ?? null),
            end: self::stringOrNull($data['end'] ?? null),
        );
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return array_filter([
            'page' => (string) $this->page,
            'limit' => (string) $this->limit,
            'platform' => $this->platform instanceof Platform ? $this->platform->value : $this->platform,
            'status' => $this->status,
            'profile_username' => $this->profile_username,
            'request_id' => $this->request_id,
            'job_id' => $this->job_id,
            'external_id' => $this->external_id,
            'start' => $this->start,
            'end' => $this->end,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }
}
