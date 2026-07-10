<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Testing;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\UploadPostClient;

final readonly class UploadPostFake
{
    private HttpFactory $httpFactory;

    private UploadPostConfig $config;

    private UploadPostClient $client;

    /**
     * @param  array<string, mixed>|callable  $responses
     */
    public function __construct(
        array|callable $responses = [],
        ?UploadPostConfig $config = null,
        ?HttpFactory $httpFactory = null,
    ) {
        $this->config = $config ?? new UploadPostConfig(apiKey: 'fake-api-key');
        $this->httpFactory = $httpFactory ?? new HttpFactory;
        $this->client = new UploadPostClient($this->config, $this->httpFactory);

        $this->fake($responses);
    }

    /**
     * @param  array<string, mixed>|callable  $responses
     */
    public static function make(array|callable $responses = [], ?UploadPostConfig $config = null): self
    {
        return new self($responses, $config);
    }

    /** @param array<string, mixed> $headers */
    public static function response(mixed $body = [], int $status = 200, array $headers = []): mixed
    {
        return (new HttpFactory)->response($body, $status, $headers);
    }

    public function client(): UploadPostClient
    {
        return $this->client;
    }

    public function http(): HttpFactory
    {
        return $this->httpFactory;
    }

    /**
     * @param  array<string, mixed>|callable  $responses
     */
    public function fake(array|callable $responses = []): self
    {
        if (is_callable($responses)) {
            $this->httpFactory->fake($responses);

            return $this;
        }

        $this->httpFactory->fake($this->normalizeResponses($responses ?: [
            '*' => ['success' => true],
        ]));

        return $this;
    }

    public function assertSent(string|callable $endpoint, ?string $method = null): self
    {
        if (is_callable($endpoint)) {
            $this->httpFactory->assertSent($endpoint);

            return $this;
        }

        $this->httpFactory->assertSent(
            fn (Request $request): bool => $this->requestMatches($request, $endpoint, $method)
        );

        return $this;
    }

    public function assertNotSent(string|callable $endpoint, ?string $method = null): self
    {
        if (is_callable($endpoint)) {
            $this->httpFactory->assertNotSent($endpoint);

            return $this;
        }

        $this->httpFactory->assertNotSent(
            fn (Request $request): bool => $this->requestMatches($request, $endpoint, $method)
        );

        return $this;
    }

    public function assertSentCount(int $count): self
    {
        $this->httpFactory->assertSentCount($count);

        return $this;
    }

    public function assertNothingSent(): self
    {
        $this->httpFactory->assertNothingSent();

        return $this;
    }

    /**
     * @param  array<string, mixed>  $responses
     * @return array<string, mixed>
     */
    private function normalizeResponses(array $responses): array
    {
        $normalized = [];

        foreach ($responses as $endpoint => $response) {
            $normalized[$this->urlPattern((string) $endpoint)] = $this->normalizeResponse($response);
        }

        return $normalized;
    }

    private function normalizeResponse(mixed $response): mixed
    {
        if (is_object($response) || is_callable($response)) {
            return $response;
        }

        return $this->httpFactory->response($response);
    }

    private function urlPattern(string $endpoint): string
    {
        if ($endpoint === '*' || str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        if (str_starts_with($endpoint, '*')) {
            return $endpoint;
        }

        return rtrim($this->config->baseUrl, '/').'/'.ltrim($endpoint, '/');
    }

    private function requestMatches(Request $request, string $endpoint, ?string $method): bool
    {
        if ($method !== null && strtoupper($request->method()) !== strtoupper($method)) {
            return false;
        }

        $url = (string) $request->url();
        if ($url === $this->urlPattern($endpoint)) {
            return true;
        }
        if (str_contains($url, $this->urlPattern($endpoint))) {
            return true;
        }

        return str_ends_with($url, '/'.ltrim($endpoint, '/'));
    }
}
