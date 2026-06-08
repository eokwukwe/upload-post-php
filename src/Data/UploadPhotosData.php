<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadPhotosData
{
    /**
     * @param  list<string|object>  $photos
     */
    public function __construct(
        public array $photos,
        public CommonUploadData $common,
        public PlatformOptions $options = new PlatformOptions
    ) {
        if ($this->photos === []) {
            throw new InvalidArgumentException('At least one photo is required.');
        }
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
