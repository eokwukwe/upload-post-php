<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class YoutubeSubtitleData
{
    use Concerns;

    public function __construct(
        public string $language,
        public string|object|null $file = null,
        public ?string $url = null,
        public ?string $name = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            language: self::stringOrNull($data['language'] ?? null) ?? '',
            file: self::mediaInputOrNull($data['file'] ?? null),
            url: self::stringOrNull($data['url'] ?? null),
            name: self::stringOrNull($data['name'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'language' => $this->language,
            'file' => $this->file,
            'url' => $this->url,
            'name' => $this->name,
        ]);
    }

    public function addTo(MultipartPayload $payload, int $index): void
    {
        $payload->field("youtube_subtitle_language_{$index}", $this->language)
            ->field("youtube_subtitle_name_{$index}", $this->name);
        if ($this->file !== null) {
            $payload->media("youtube_subtitle_file_{$index}", $this->file instanceof Media ? $this->file : Media::from($this->file));
        } elseif ($this->url !== null) {
            $payload->field("youtube_subtitle_file_{$index}", $this->url);
        }
    }
}
