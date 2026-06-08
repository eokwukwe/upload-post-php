<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Support;

use InvalidArgumentException;

final readonly class UploadPostConfig
{
    public function __construct(
        public string $apiKey,
        public string $baseUrl = 'https://api.upload-post.com/api',
        public int $timeout = 300,
        public int $connectTimeout = 30,
        public int $retryTimes = 2,
        public int $retrySleepMs = 500,
        public bool $throwOnValidation = true,
    ) {
        if (trim($this->apiKey) === '') {
            throw new InvalidArgumentException('Upload-Post API key is required.');
        }
    }

    /**
     * @param  array<string,mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $throwOnValidation = filter_var(
            $config['throw_on_validation'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        return new self(
            apiKey: (string) ($config['api_key'] ?? ''),
            baseUrl: rtrim((string) ($config['base_url'] ?? 'https://api.upload-post.com/api'), '/'),
            timeout: (int) ($config['timeout'] ?? 300),
            connectTimeout: (int) ($config['connect_timeout'] ?? 30),
            retryTimes: (int) ($config['retry_times'] ?? 2),
            retrySleepMs: (int) ($config['retry_sleep_ms'] ?? 500),
            throwOnValidation: $throwOnValidation ?? true,
        );
    }
}
