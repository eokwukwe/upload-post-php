<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadTextData
{
    use Concerns;

    public function __construct(
        public CommonUploadData $common,
        public ?string $link_url = null,
        public PlatformOptions $options = new PlatformOptions,
        public ?string $idempotency_key = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            common: self::commonFrom($data),
            link_url: self::stringOrNull($data['link_url'] ?? null),
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
            'common' => $this->common->toArray(),
            'link_url' => $this->link_url,
            'options' => $this->options->toArray(),
            'idempotency_key' => $this->idempotency_key,
        ]);
    }

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        $this->common->addCommonTo($payload);
        $payload->field('link_url', $this->link_url);
        $this->options->addForText($payload, $this->common->platforms, $this->link_url);

        return $payload;
    }
}
