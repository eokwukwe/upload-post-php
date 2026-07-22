<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Exceptions;

use Illuminate\Http\Client\Response;

final class UploadPostValidationException extends UploadPostException
{
    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : null;

        return new self(
            sprintf('Upload-Post API error [%s]: %s', $response->status(), self::responseMessage($response, $payload)),
            $response->status(),
            $payload,
        );
    }
}
