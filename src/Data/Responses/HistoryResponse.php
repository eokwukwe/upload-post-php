<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\HistoryItemData;
use Softgeng\UploadPost\Support\Arr;

final readonly class HistoryResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<HistoryItemData>  $history
     * @param  list<HistoryItemData>  $in_progress
     */
    public function __construct(
        array $raw,
        public array $history = [],
        public ?int $total = null,
        public ?int $page = null,
        public ?int $limit = null,
        public array $in_progress = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::historyFrom(Arr::get($raw, 'history') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::intOrNull(Arr::get($raw, 'total')),
            self::intOrNull(Arr::get($raw, 'page')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::historyFrom(Arr::get($raw, 'in_progress')),
        );
    }
}
