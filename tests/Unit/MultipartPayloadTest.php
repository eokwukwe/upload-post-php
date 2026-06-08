<?php

declare(strict_types=1);

use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\MultipartPayload;

test('multipart payload converts arrays booleans and enums', function (): void {
    $parts = (new MultipartPayload)
        ->field('platform[]', [Platform::TikTok, Platform::YouTube])
        ->field('async_upload', true)
        ->field('empty', null)
        ->all();

    expect($parts)->toHaveCount(3)
        ->and($parts[0]['contents'])->toBe('tiktok')
        ->and($parts[1]['contents'])->toBe('youtube')
        ->and($parts[2]['contents'])->toBe('true');
});
