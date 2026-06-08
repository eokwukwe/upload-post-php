<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadVideoData
{
    public function __construct(public string|object $video, public CommonUploadData $common, public PlatformOptions $options = new PlatformOptions) {}

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        $payload->media('video', Media::from($this->video));
        $this->common->addCommonTo($payload);
        $this->options->addForVideo($payload);

        return $payload;
    }
}
