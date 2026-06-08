<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\Platform;

final readonly class AnalyticsQueryData
{
    use Concerns;

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function __construct(
        public array $platforms = [],
        public ?string $page_id = null,
        public ?string $page_urn = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            platforms: self::platformListFrom($data['platforms'] ?? []),
            page_id: self::stringOrNull($data['page_id'] ?? null),
            page_urn: self::stringOrNull($data['page_urn'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'platforms' => self::platformsToValues($this->platforms),
            'page_id' => $this->page_id,
            'page_urn' => $this->page_urn,
        ]);
    }

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
