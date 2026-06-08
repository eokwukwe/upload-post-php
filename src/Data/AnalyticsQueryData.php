<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\Platform;

final readonly class AnalyticsQueryData
{
    /** 
     * @param list<Platform|string> $platforms 
     */
    public function __construct(
        public array $platforms = [],
        public ?string $page_id = null,
        public ?string $page_urn = null,
    ) {}

    /** 
     * @return array<string,string> 
     */
    public function toQuery(): array
    {
        $platforms = array_map(static fn (\Softgeng\UploadPost\Enums\Platform|string $p) => $p instanceof Platform ? $p->value : $p, $this->platforms);

        return array_filter([
            'platforms' => $platforms === [] ? null : implode(',', $platforms),
            'page_id' => $this->page_id,
            'page_urn' => $this->page_urn,
        ], static fn (?string $v): bool => $v !== null && $v !== '');
    }
}
