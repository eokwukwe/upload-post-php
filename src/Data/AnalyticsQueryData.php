<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;

final readonly class AnalyticsQueryData
{
    use InteractsWithData;

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function __construct(
        public array $platforms = [],
        public ?string $page_id = null,
        public ?int $days = null,
        public ?string $page_urn = null,
    ) {
        if ($this->days !== null && ($this->days < 1 || $this->days > 365)) {
            throw new InvalidArgumentException('Analytics days must be between 1 and 365.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            platforms: self::platformListFrom($data['platforms'] ?? []),
            page_id: self::stringOrNull($data['page_id'] ?? null),
            days: self::intOrNull($data['days'] ?? null),
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
            'days' => $this->days,
            'page_urn' => $this->page_urn,
        ]);
    }

    /**
     * @return array<string,string>
     */
    public function toQuery(): array
    {
        $platforms = array_values(array_filter(
            array_map(
                static fn (Platform|string $platform): string => trim($platform instanceof Platform ? $platform->value : $platform),
                $this->platforms,
            ),
            static fn (string $platform): bool => $platform !== '',
        ));

        return array_filter([
            'platforms' => $platforms === [] ? null : implode(',', $platforms),
            'page_id' => $this->page_id,
            'days' => $this->days === null ? null : (string) $this->days,
            'page_urn' => $this->page_urn,
        ], static fn (?string $v): bool => $v !== null && $v !== '');
    }
}
