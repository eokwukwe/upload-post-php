<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Data\UserProfileData;
use Softgeng\UploadPost\Support\Arr;

final readonly class UserProfilesResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<UserProfileData>  $profiles
     */
    public function __construct(
        array $raw,
        public ?bool $success = null,
        public array $profiles = [],
        public ?int $limit = null,
        public ?string $plan = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::userProfilesFrom(Arr::get($raw, 'profiles') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::stringOrNull(Arr::get($raw, 'plan')),
        );
    }
}
