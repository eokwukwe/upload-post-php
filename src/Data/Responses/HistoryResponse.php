<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class HistoryResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $history
     */
    public function __construct(
        array $raw,
        public array $history = [],
        public ?int $total = null,
        public ?int $page = null,
        public ?int $limit = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * Backward-compatible alias for older ListResponse usage.
     *
     * @return array<int|string, mixed>|null
     */
    public function __get(string $name): mixed
    {
        if ($name === 'items') {
            return $this->history;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::arrayOrEmpty(Arr::get($raw, 'history') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::intOrNull(Arr::get($raw, 'total')),
            self::intOrNull(Arr::get($raw, 'page')),
            self::intOrNull(Arr::get($raw, 'limit')),
        );
    }
}
