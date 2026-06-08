<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadTextData
{
    public function __construct(
        public CommonUploadData $common,
        public ?string $link_url = null,
        public PlatformOptions $options = new PlatformOptions,
    ) {}

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        $this->common->addCommonTo($payload);
        $payload->field('link_url', $this->link_url);
        $this->options->addForText($payload, $this->link_url);

        return $payload;
    }
}
