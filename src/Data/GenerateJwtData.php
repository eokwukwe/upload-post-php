<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\Platform;

final readonly class GenerateJwtData
{
    use Concerns;

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function __construct(
        public string $username,
        public ?string $redirect_url = null,
        public ?string $logo_image = null,
        public ?string $redirect_button_text = null,
        public array $platforms = [],
        public ?bool $show_calendar = null,
        public ?bool $readonly_calendar = null,
        public ?string $connect_title = null,
        public ?string $connect_description = null,
        public ?string $language = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: self::stringOrNull($data['username'] ?? null) ?? '',
            redirect_url: self::stringOrNull($data['redirect_url'] ?? null),
            logo_image: self::stringOrNull($data['logo_image'] ?? null),
            redirect_button_text: self::stringOrNull($data['redirect_button_text'] ?? null),
            platforms: self::platformListFrom($data['platforms'] ?? []),
            show_calendar: self::boolOrNull($data['show_calendar'] ?? null),
            readonly_calendar: self::boolOrNull($data['readonly_calendar'] ?? null),
            connect_title: self::stringOrNull($data['connect_title'] ?? null),
            connect_description: self::stringOrNull($data['connect_description'] ?? null),
            language: self::stringOrNull($data['language'] ?? null),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $platforms = array_map(
            static fn (\Softgeng\UploadPost\Enums\Platform|string $p) => $p instanceof Platform ? $p->value : $p,
            $this->platforms
        );

        return array_filter([
            'username' => $this->username,
            'redirect_url' => $this->redirect_url,
            'logo_image' => $this->logo_image,
            'redirect_button_text' => $this->redirect_button_text,
            'platforms' => $platforms === [] ? null : $platforms,
            'show_calendar' => $this->show_calendar,
            'readonly_calendar' => $this->readonly_calendar,
            'connect_title' => $this->connect_title,
            'connect_description' => $this->connect_description,
            'language' => $this->language,
        ], static fn ($v): bool => $v !== null && $v !== '');
    }
}
