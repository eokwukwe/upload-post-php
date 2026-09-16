<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\UserProfileData;
use Softgeng\UploadPost\Support\Arr;

final readonly class UserResponse extends ApiResponse
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?string $username = null,
        public ?bool $success = null,
        public ?UserProfileData $profile = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $profile = Arr::get($raw, 'profile');
        $profileData = is_array($profile) ? UserProfileData::fromArray($profile) : null;

        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'username') ?? Arr::get($raw, 'user') ?? $profileData?->username),
            self::boolOrNull(Arr::get($raw, 'success')),
            $profileData,
        );
    }
}
