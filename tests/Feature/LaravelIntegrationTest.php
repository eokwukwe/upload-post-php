<?php

declare(strict_types=1);

use Softgeng\UploadPost\Facades\UploadPost;
use Softgeng\UploadPost\UploadPostClient;

it('merges the package config', function (): void {
    expect(config('upload-post.base_url'))->toBe('https://api.upload-post.com/api')
        ->and(config('upload-post.api_key'))->toBe('test-key');
});

it('registers the upload post client in the container', function (): void {
    expect(app(UploadPostClient::class))->toBeInstanceOf(UploadPostClient::class);
});

it('registers the upload post facade accessor', function (): void {
    expect(UploadPost::getFacadeRoot())->toBeInstanceOf(UploadPostClient::class);
});
