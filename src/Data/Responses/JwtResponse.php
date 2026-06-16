<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

use Softgeng\UploadPost\Support\Arr;

final readonly class JwtResponse extends ApiResponse
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        array $raw,
        public ?string $jwt = null,
        public ?string $url = null,
        public ?bool $success = null,
        public ?string $access_url = null,
        public ?int $duration = null,
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $accessUrl = self::stringOrNull(Arr::get($raw, 'access_url'));

        return new self(
            $raw,
            self::stringOrNull(Arr::get($raw, 'jwt') ?? Arr::get($raw, 'token')),
            self::stringOrNull(Arr::get($raw, 'url') ?? Arr::get($raw, 'connect_url') ?? $accessUrl),
            self::boolOrNull(Arr::get($raw, 'success')),
            $accessUrl,
            self::intOrNull(Arr::get($raw, 'duration')),
        );
    }
}
