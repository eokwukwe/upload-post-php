<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class UserProfilesResponse extends ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int|string, mixed>  $profiles
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
     * Backward-compatible alias for older ListResponse usage.
     *
     * @return array<int|string, mixed>|null
     */
    public function __get(string $name): mixed
    {
        if ($name === 'items') {
            return $this->profiles;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            $raw,
            self::boolOrNull(Arr::get($raw, 'success')),
            self::arrayOrEmpty(Arr::get($raw, 'profiles') ?? Arr::get($raw, 'data') ?? Arr::get($raw, 'items')),
            self::intOrNull(Arr::get($raw, 'limit')),
            self::stringOrNull(Arr::get($raw, 'plan')),
        );
    }
}
