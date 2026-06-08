<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeInterface;
use InvalidArgumentException;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadDocumentData
{
    use Concerns;

    public function __construct(
        public string|object $document,
        public string $user,
        public string $title,
        public ?string $description = null,
        public DateTimeInterface|string|null $scheduled_date = null,
        public ?string $timezone = null,
        public ?bool $add_to_queue = null,
        public ?int $max_posts_per_slot = null,
        public ?bool $async_upload = null,
        public PlatformOptions $options = new PlatformOptions,
        public ?string $idempotency_key = null,
    ) {
        if (trim($this->user) === '') {
            throw new InvalidArgumentException('user is required.');
        }

        if (trim($this->title) === '') {
            throw new InvalidArgumentException('title is required.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            document: self::mediaInputFrom($data['document'] ?? null),
            user: self::stringOrNull($data['user'] ?? null) ?? '',
            title: self::stringOrNull($data['title'] ?? null) ?? '',
            description: self::stringOrNull($data['description'] ?? null),
            scheduled_date: self::dateFrom($data['scheduled_date'] ?? null),
            timezone: self::stringOrNull($data['timezone'] ?? null),
            add_to_queue: self::boolOrNull($data['add_to_queue'] ?? null),
            max_posts_per_slot: self::intOrNull($data['max_posts_per_slot'] ?? null),
            async_upload: self::boolOrNull($data['async_upload'] ?? null),
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
            'document' => $this->document,
            'user' => $this->user,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_date' => self::date($this->scheduled_date),
            'timezone' => $this->timezone,
            'add_to_queue' => $this->add_to_queue,
            'max_posts_per_slot' => $this->max_posts_per_slot,
            'async_upload' => $this->async_upload,
            'options' => $this->options->toArray(),
            'idempotency_key' => $this->idempotency_key,
        ]);
    }

    public function toMultipart(): MultipartPayload
    {
        $scheduled_date_value = $this->scheduled_date instanceof DateTimeInterface
            ? $this->scheduled_date->format(DateTimeInterface::ATOM)
            : $this->scheduled_date;

        $payload = new MultipartPayload;
        $payload->media('document', Media::from($this->document))
            ->field('user', $this->user)
            ->field('title', $this->title)
            ->field('platform[]', Platform::LinkedIn->value)
            ->field('description', $this->description)
            ->field('scheduled_date', $scheduled_date_value)
            ->field('timezone', $this->timezone)
            ->field('add_to_queue', $this->add_to_queue)
            ->field('max_posts_per_slot', $this->max_posts_per_slot)
            ->field('async_upload', $this->async_upload);

        $this->options->addForDocument($payload);

        return $payload;
    }
}
