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
    ) {
        if (trim($this->user) === '') {
            throw new InvalidArgumentException('user is required.');
        }

        if (trim($this->title) === '') {
            throw new InvalidArgumentException('title is required.');
        }
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
