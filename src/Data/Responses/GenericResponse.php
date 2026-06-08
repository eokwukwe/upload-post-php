<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data\Responses;

final readonly class GenericResponse extends ApiResponse
{
    /** @param array<string,mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self($raw);
    }
}
