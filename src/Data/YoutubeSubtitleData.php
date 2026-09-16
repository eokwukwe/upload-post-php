<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class YoutubeSubtitleData
{
    use InteractsWithData;

    public function __construct(
        public string $language,
        public string|object $file,
        public ?string $name = null
    ) {
        if (trim($this->language) === '') {
            throw new InvalidArgumentException('language is required for YouTube subtitles.');
        }

        if (is_string($this->file) && trim($this->file) === '') {
            throw new InvalidArgumentException('file is required for YouTube subtitles.');
        }

        if (self::mediaIsUrl($this->file)) {
            throw new InvalidArgumentException('YouTube subtitle files must be uploaded files or local file paths.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            language: self::stringOrNull($data['language'] ?? null) ?? '',
            file: self::mediaInputFrom($data['file'] ?? null),
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
            'name' => $this->name,
        ]);
    }

    public function addTo(MultipartPayload $payload, int $index): void
    {
        $payload->field("youtube_subtitle_language_{$index}", $this->language)
            ->field("youtube_subtitle_name_{$index}", $this->name);

        $payload->media(
            "youtube_subtitle_file_{$index}",
            $this->file instanceof Media ? $this->file : Media::from($this->file)
        );
    }
}
