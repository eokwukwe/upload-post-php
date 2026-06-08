<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadPhotosData
{
    use Concerns;

    /**
     * @param  list<string|object>  $photos
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
        $this->common->addCommonTo($payload);
        $this->options->addForPhotos($payload, $this->common->platforms);

        return $payload;
    }
}
