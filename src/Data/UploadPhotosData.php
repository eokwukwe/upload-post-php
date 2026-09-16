<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadPhotosData
{
    use InteractsWithData;

    /**
     * @param  list<mixed>  $photos
     */
    public function __construct(
        public array $photos,
        public CommonUploadData $common,
        public PlatformOptions $options = new PlatformOptions,
        public ?string $idempotency_key = null,
    ) {
        if ($this->photos === []) {
            throw new InvalidArgumentException('At least one photo is required.');
        }

        foreach ($this->photos as $photo) {
            if (! is_string($photo) && ! is_object($photo)) {
                throw new InvalidArgumentException('Invalid media for photos[].');
            }

            Media::from($photo)->validate('photos[]');
        }

        $platforms = self::platformsToValues($this->common->platforms);

        if (in_array(Platform::YouTube->value, $platforms, true)) {
            throw new InvalidArgumentException('YouTube is not supported for photo uploads.');
        }

        if (
            in_array(Platform::Pinterest->value, $platforms, true) &&
            trim($this->options->pinterest_board_id ?? '') === ''
        ) {
            throw new InvalidArgumentException('pinterest_board_id is required for Pinterest uploads.');
        }

        $this->options->validateForPhotos($this->common->platforms);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            photos: self::mediaListFrom($data['photos'] ?? $data['photo'] ?? []),
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
            'photos' => $this->photos,
            'common' => $this->common->toArray(),
            'options' => $this->options->toArray(),
            'idempotency_key' => $this->idempotency_key,
        ]);
    }

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        foreach ($this->photos as $photo) {
            $payload->media('photos[]', Media::from($photo));
        }
        $this->common->addForPhotosTo($payload);
        $this->options->addForPhotos($payload, $this->common->platforms);

        return $payload;
    }
}
