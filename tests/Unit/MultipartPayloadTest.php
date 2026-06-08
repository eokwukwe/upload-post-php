<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadTextData;
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

test('upload text only includes options for selected platforms', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
        options: new PlatformOptions(
            facebook_page_id: 'facebook-page',
            x_quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)
        ->toContain('quote_tweet_id')
        ->not->toContain('facebook_page_id');
});

test('upload text keeps options for selected platforms', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Facebook], title: 'Hello'),
        options: new PlatformOptions(
            facebook_page_id: 'facebook-page',
            x_quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)
        ->toContain('facebook_page_id')
        ->not->toContain('quote_tweet_id');
});
