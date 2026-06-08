<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
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

test('upload video builds node-compatible multipart fields for selected platforms', function (): void {
    $parts = (new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok, Platform::YouTube], title: 'Video'),
        options: new PlatformOptions(
            tiktok_disable_comment: true,
            youtube_tags: ['sdk'],
            facebook_page_id: 'facebook-page',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($contents['video'])->toBe('https://example.com/video.mp4')
        ->and($names)->toContain('platform[]', 'disable_comment', 'tags[]')
        ->and($names)->not->toContain('facebook_page_id');
});

test('upload photos builds node-compatible multipart fields for selected platforms', function (): void {
    $parts = (new UploadPhotosData(
        photos: ['https://example.com/photo.jpg'],
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram, Platform::Pinterest], title: 'Photos'),
        options: new PlatformOptions(
            instagram_media_type: 'IMAGE',
            pinterest_board_id: 'board-123',
            x_quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($contents['photos[]'])->toBe('https://example.com/photo.jpg')
        ->and($names)->toContain('platform[]', 'media_type', 'pinterest_board_id')
        ->and($names)->not->toContain('quote_tweet_id');
});

test('upload document forces linkedin multipart fields', function (): void {
    $parts = (new UploadDocumentData(
        document: 'https://example.com/deck.pdf',
        user: 'profile',
        title: 'Document',
        options: new PlatformOptions(
            linkedin_visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
        ),
    ))->toMultipart()->all();

    $contents = array_column($parts, 'contents', 'name');

    expect($contents['document'])->toBe('https://example.com/deck.pdf')
        ->and($contents['platform[]'])->toBe('linkedin')
        ->and($contents['visibility'])->toBe('PUBLIC')
        ->and($contents)->not->toHaveKey('facebook_page_id');
});
