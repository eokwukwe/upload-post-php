<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Support\Arr;

final readonly class UserProfileData extends ResponseData
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $social_accounts
     * @param  array<string, string>  $ui_labels
     */
    public function __construct(
        array $raw,
        public ?string $username = null,
        public ?string $created_at = null,
        public array $social_accounts = [],
        public array $ui_labels = [],
        public ?string $selected_location_id = null,
        public ?string $selected_location_name = null,
        public ?string $facebook_page_id = null,
        public ?string $facebook_page_name = null,
        public ?string $linkedin_page_id = null,
        public ?string $linkedin_page_name = null,
        public ?string $selected_page_id = null,
        public ?string $selected_page_name = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'username')),
            self::stringOrNull(Arr::get($raw, 'created_at')),
            self::socialAccountsFrom(Arr::get($raw, 'social_accounts')),
            self::uiLabelsFrom(Arr::get($raw, 'ui_labels')),
            self::stringOrNull(Arr::get($raw, 'selected_location_id')),
            self::stringOrNull(Arr::get($raw, 'selected_location_name')),
            self::stringOrNull(Arr::get($raw, 'facebook_page_id')),
            self::stringOrNull(Arr::get($raw, 'facebook_page_name')),
            self::stringOrNull(Arr::get($raw, 'linkedin_page_id')),
            self::stringOrNull(Arr::get($raw, 'linkedin_page_name')),
            self::stringOrNull(Arr::get($raw, 'selected_page_id')),
            self::stringOrNull(Arr::get($raw, 'selected_page_name')),
        );
    }

    /** @return array<string, mixed> */
    private static function socialAccountsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $accounts = [];
        foreach ($value as $platform => $account) {
            if (is_string($platform)) {
                $accounts[$platform] = $account;
            }
        }

        return $accounts;
    }

    /** @return array<string, string> */
    private static function uiLabelsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_filter(
            $value,
            static fn (mixed $label, mixed $key): bool => is_string($key) && is_string($label),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
