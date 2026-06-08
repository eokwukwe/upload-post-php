<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\Platform;

final readonly class GenerateJwtData
{
    /** @param list<Platform|string> $platforms */
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

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $platforms = array_map(static fn (\Softgeng\UploadPost\Enums\Platform|string $p) => $p instanceof Platform ? $p->value : $p, $this->platforms);

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
