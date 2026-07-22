<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;
use Throwable;

class UploadPostException extends Exception
{
    /**
     * @param  array<string,mixed>|null  $payload
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

        return new self(
            sprintf('Upload-Post API error [%s]: %s', $response->status(), self::responseMessage($response, $payload)),
            $response->status(),
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected static function responseMessage(Response $response, ?array $payload): string
    {
        $body = $response->body();
        $message = $payload['message']
            ?? $payload['detail']
            ?? $payload['error']
            ?? null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        if (is_scalar($message)) {
            return (string) $message;
        }

        if (is_array($message) || is_object($message)) {
            $encoded = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (is_string($encoded)) {
                return $encoded;
            }
        }

        return $body !== '' ? $body : 'Unknown API error';
    }
}
