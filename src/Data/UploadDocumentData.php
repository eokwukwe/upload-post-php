<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeInterface;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class UploadDocumentData
{
    use InteractsWithData;

    public function __construct(
        public string|object $document,
        public string $user,
        public string $title,
        public ?string $description = null,
        public PlatformOptions $options = new PlatformOptions,
        public ?string $first_comment = null,
        public ?string $linkedin_first_comment = null,
        public DateTimeInterface|string|null $scheduled_date = null,
        public ?string $timezone = null,
    ) {
        Media::from($this->document)->validate('document');

        if (trim($this->user) === '') {
            throw new InvalidArgumentException('user is required.');
        }

        if (trim($this->title) === '') {
            throw new InvalidArgumentException('title is required.');
        }

        $this->options->validateForDocument();
        self::validateScheduledDate($this->scheduled_date, $this->timezone, false);
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
            options: self::optionsFrom($data),
            first_comment: self::stringOrNull($data['first_comment'] ?? null),
            linkedin_first_comment: self::stringOrNull($data['linkedin_first_comment'] ?? null),
            scheduled_date: self::dateFrom($data['scheduled_date'] ?? null),
            timezone: self::stringOrNull($data['timezone'] ?? null),
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
            'first_comment' => $this->first_comment,
            'linkedin_first_comment' => $this->linkedin_first_comment,
            'scheduled_date' => self::date($this->scheduled_date),
            'timezone' => $this->timezone,
            'options' => $this->options->toArray(),
        ]);
    }

    public function toMultipart(): MultipartPayload
    {
        $payload = new MultipartPayload;
        $payload->media('document', Media::from($this->document))
            ->field('user', $this->user)
            ->field('title', $this->title)
            ->field('platform[]', Platform::LinkedIn->value)
            ->field('description', $this->description)
            ->field('first_comment', $this->first_comment)
            ->field('linkedin_first_comment', $this->linkedin_first_comment)
            ->field('scheduled_date', self::date($this->scheduled_date))
            ->field('timezone', $this->timezone);

        $this->options->addForDocument($payload);

        return $payload;
    }
}
