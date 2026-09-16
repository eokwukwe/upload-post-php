<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\UserPreferencesData;
use Softgeng\UploadPost\Support\Arr;

final readonly class UserPreferencesResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public ?UserPreferencesData $preferences = null,
    ) {
        parent::__construct($raw);
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            is_array(Arr::get($raw, 'preferences'))
                ? UserPreferencesData::fromArray(Arr::get($raw, 'preferences'))
                : null,
        );
    }
}
