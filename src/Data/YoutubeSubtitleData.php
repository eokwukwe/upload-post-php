<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class YoutubeSubtitleData
{
    public function __construct(public string $language, public string|Media|null $file = null, public ?string $url = null, public ?string $name = null) {}

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
