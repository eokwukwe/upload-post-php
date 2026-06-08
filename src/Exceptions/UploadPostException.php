<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;
use Throwable;

class UploadPostException extends Exception
{
    /** 
     * @param array<string,mixed>|null $payload 
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

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
