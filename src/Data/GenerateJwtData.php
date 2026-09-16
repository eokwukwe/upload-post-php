<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\JwtLanguage;
use Softgeng\UploadPost\Enums\Platform;

final readonly class GenerateJwtData
{
    use InteractsWithData;

    /**
     * @param  list<Platform|string>  $platforms
     * @param  array<array-key, mixed>|null  $ui_labels
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
        public JwtLanguage|string|null $language = null,
        public ?array $ui_labels = null,
    ) {
        if (trim($this->username) === '') {
            throw new InvalidArgumentException('username is required.');
        }

        if ($this->redirect_url !== null && trim($this->redirect_url) !== '') {
            if (mb_strlen($this->redirect_url) > 2000) {
                throw new InvalidArgumentException('redirect_url must be 2000 characters or fewer.');
            }

            $parts = parse_url($this->redirect_url);
            $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : null;
            $host = is_array($parts) ? ($parts['host'] ?? null) : null;

            if (
                filter_var($this->redirect_url, FILTER_VALIDATE_URL) === false
                || ! in_array($scheme, ['http', 'https'], true)
                || ! is_string($host)
                || trim($host) === ''
            ) {
                throw new InvalidArgumentException('redirect_url must be an absolute HTTP(S) URL.');
            }
        }

        $language = self::enumValue($this->language);

        if ($language !== null && trim((string) $language) !== '' && JwtLanguage::tryFrom((string) $language) === null) {
            throw new InvalidArgumentException('language must be one of: en, es, de, fr, pt, pl, tr.');
        }

        if ($this->ui_labels !== null) {
            if (count($this->ui_labels) > 100) {
                throw new InvalidArgumentException('ui_labels cannot contain more than 100 entries.');
            }

            foreach ($this->ui_labels as $key => $label) {
                if (! is_string($key) || ! preg_match('/^[A-Za-z0-9._]+$/D', $key)) {
                    throw new InvalidArgumentException('ui_labels keys may only contain letters, numbers, dots, and underscores.');
                }

                if (! is_string($label) || mb_strlen($label) > 300) {
                    throw new InvalidArgumentException('ui_labels values must be strings of 300 characters or fewer.');
                }
            }
        }
    }

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
            language: self::languageFrom($data['language'] ?? null),
            ui_labels: self::uiLabelsFrom($data['ui_labels'] ?? null),
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

        $result = array_filter([
            'username' => $this->username,
            'redirect_url' => $this->redirect_url,
            'logo_image' => $this->logo_image,
            'redirect_button_text' => $this->redirect_button_text,
            'platforms' => $platforms === [] ? null : $platforms,
            'show_calendar' => $this->show_calendar,
            'readonly_calendar' => $this->readonly_calendar,
            'connect_title' => $this->connect_title,
            'connect_description' => $this->connect_description,
            'language' => self::enumValue($this->language),
        ], static fn ($v): bool => $v !== null && $v !== '');

        if ($this->ui_labels !== null) {
            $result['ui_labels'] = $this->ui_labels;
        }

        return $result;
    }

    /** @return array<string, string>|null */
    private static function uiLabelsFrom(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        return array_filter(
            $value,
            static fn (mixed $label, mixed $key): bool => is_string($key) && is_string($label),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private static function languageFrom(mixed $value): JwtLanguage|string|null
    {
        return $value instanceof JwtLanguage ? $value : self::stringOrNull($value);
    }
}
