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

        $body = $response->body();
        $message = $payload['message']
            ?? $payload['detail']
            ?? $payload['error']
            ?? ($body !== '' ? $body : 'Unknown API error');

        return new self(
            sprintf('Upload-Post API error [%s]: %s', $response->status(), $message),
            $response->status(),
            $payload,
        );
    }
}
