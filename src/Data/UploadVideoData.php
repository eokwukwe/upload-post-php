<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadVideoData
{
    use InteractsWithData;

    public function __construct(
        public string|object $video,
        public CommonUploadData $common,
        public PlatformOptions $options = new PlatformOptions,
        public ?string $idempotency_key = null,
    ) {
        Media::from($this->video)->validate('video');

        $platforms = self::platformsToValues($this->common->platforms);
        $title = trim($this->common->title ?? '');

        if (in_array(Platform::YouTube->value, $platforms, true) && $title === '') {
            throw new InvalidArgumentException('title is required for YouTube video uploads.');
        }

        if (
            in_array(Platform::Pinterest->value, $platforms, true) &&
            trim($this->options->pinterest_board_id ?? '') === ''
        ) {
            throw new InvalidArgumentException('pinterest_board_id is required for Pinterest uploads.');
        }

        $this->options->validateForVideo($this->common->platforms);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            video: self::mediaInputFrom($data['video'] ?? null),
            common: self::commonFrom($data),
            options: self::optionsFrom($data),
            idempotency_key: self::stringOrNull($data['idempotency_key'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'video' => $this->video,
            'common' => $this->common->toArray(),
            'options' => $this->options->toArray(),
            'idempotency_key' => $this->idempotency_key,
        ]);
    }

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        $payload->media('video', Media::from($this->video));
        $this->common->addForVideoTo($payload);
        $this->options->addForVideo($payload, $this->common->platforms);

        return $payload;
    }
}
