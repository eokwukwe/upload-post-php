<?php

declare(strict_types=1);

return [
    'api_key' => env('UPLOAD_POST_API_KEY'),
    'base_url' => env('UPLOAD_POST_BASE_URL', 'https://api.upload-post.com/api'),
    'source' => env('UPLOAD_POST_SOURCE', 'php-laravel'),
    'timeout' => (int) env('UPLOAD_POST_TIMEOUT', 300),
    'connect_timeout' => (int) env('UPLOAD_POST_CONNECT_TIMEOUT', 30),
    'retry_times' => (int) env('UPLOAD_POST_RETRY_TIMES', 2),
    'retry_sleep_ms' => (int) env('UPLOAD_POST_RETRY_SLEEP_MS', 500),
    'throw_on_validation' => true,
];
