<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\Responses\GenericResponse;
use Softgeng\UploadPost\Data\Responses\JwtResponse;
use Softgeng\UploadPost\Data\Responses\ListResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

test('analytics query data maps platform enums and optional query values', function (): void {
    $query = (new AnalyticsQueryData(
        platforms: [Platform::Instagram, 'youtube'],
        page_id: 'page-123',
        page_urn: 'urn:li:page:123',
    ))->toQuery();

    expect($query)->toBe([
        'platforms' => 'instagram,youtube',
        'page_id' => 'page-123',
        'page_urn' => 'urn:li:page:123',
    ]);
});

test('analytics query data drops empty optional values', function (): void {
    expect((new AnalyticsQueryData)->toQuery())->toBe([]);
});

test('generate jwt data maps platform enums and removes blank values', function (): void {
    $data = (new GenerateJwtData(
        username: 'profile',
        redirect_url: 'https://example.com/callback',
        logo_image: '',
        redirect_button_text: 'Return',
        platforms: [Platform::TikTok, 'x'],
        show_calendar: true,
        readonly_calendar: false,
        connect_title: 'Connect accounts',
        connect_description: 'Choose platforms',
        language: 'en',
    ))->toArray();

    expect($data)->toBe([
        'username' => 'profile',
        'redirect_url' => 'https://example.com/callback',
        'redirect_button_text' => 'Return',
        'platforms' => ['tiktok', 'x'],
        'show_calendar' => true,
        'readonly_calendar' => false,
        'connect_title' => 'Connect accounts',
        'connect_description' => 'Choose platforms',
        'language' => 'en',
    ]);
});

test('response DTOs expose typed fields and raw payloads', function (): void {
    $generic = GenericResponse::fromArray(['nested' => ['value' => 'ok']]);
    $jwt = JwtResponse::fromArray(['token' => 'jwt-token', 'connect_url' => 'https://connect.example.com']);
    $listFromData = ListResponse::fromArray(['data' => [['id' => 1]]]);
    $listFromItems = ListResponse::fromArray(['items' => [['id' => 2]]]);
    $listFromRawList = ListResponse::fromArray([['id' => 3]]);
    $status = StatusResponse::fromArray(['status' => 'done', 'request_id' => 123, 'job_id' => 'job']);
    $upload = UploadResponse::fromArray(['request_id' => 123, 'job_id' => 456, 'status' => 'queued', 'message' => 'ok']);
    $user = UserResponse::fromArray(['user' => 'profile']);

    expect($generic->get('nested.value'))->toBe('ok')
        ->and($generic->toArray())->toBe(['nested' => ['value' => 'ok']])
        ->and($jwt->jwt)->toBe('jwt-token')
        ->and($jwt->url)->toBe('https://connect.example.com')
        ->and($listFromData->items)->toBe([['id' => 1]])
        ->and($listFromItems->items)->toBe([['id' => 2]])
        ->and($listFromRawList->items)->toBe([['id' => 3]])
        ->and($status->status)->toBe('done')
        ->and($status->request_id)->toBe('123')
        ->and($status->job_id)->toBe('job')
        ->and($upload->request_id)->toBe('123')
        ->and($upload->job_id)->toBe('456')
        ->and($upload->status)->toBe('queued')
        ->and($upload->message)->toBe('ok')
        ->and($user->username)->toBe('profile');
});

test('response DTOs convert empty values to null', function (): void {
    expect(JwtResponse::fromArray(['jwt' => '', 'url' => ''])->jwt)->toBeNull()
        ->and(JwtResponse::fromArray(['jwt' => '', 'url' => ''])->url)->toBeNull()
        ->and(StatusResponse::fromArray(['status' => '', 'request_id' => '', 'job_id' => ''])->status)->toBeNull()
        ->and(UploadResponse::fromArray(['request_id' => '', 'job_id' => '', 'status' => '', 'message' => ''])->request_id)->toBeNull()
        ->and(UserResponse::fromArray(['username' => ''])->username)->toBeNull();
});

test('youtube subtitle data adds url subtitle fields', function (): void {
    $payload = new MultipartPayload;

    (new YoutubeSubtitleData(language: 'en', url: 'https://example.com/subtitles.vtt', name: 'English'))->addTo($payload, 0);

    expect($payload->all())->toBe([
        ['name' => 'youtube_subtitle_language_0', 'contents' => 'en'],
        ['name' => 'youtube_subtitle_name_0', 'contents' => 'English'],
        ['name' => 'youtube_subtitle_file_0', 'contents' => 'https://example.com/subtitles.vtt'],
    ]);
});

test('youtube subtitle data adds media subtitle fields', function (): void {
    $payload = new MultipartPayload;

    (new YoutubeSubtitleData(language: 'en', file: Media::from('https://example.com/subtitles.vtt')))->addTo($payload, 1);

    expect($payload->all())->toBe([
        ['name' => 'youtube_subtitle_language_1', 'contents' => 'en'],
        ['name' => 'youtube_subtitle_file_1', 'contents' => 'https://example.com/subtitles.vtt'],
    ]);
});

test('common upload data validates required fields and formats dates and comment media', function (): void {
    expect(fn (): CommonUploadData => new CommonUploadData(user: '', platforms: [Platform::X]))
        ->toThrow(InvalidArgumentException::class, 'user is required.')
        ->and(fn (): CommonUploadData => new CommonUploadData(user: 'profile', platforms: []))
        ->toThrow(InvalidArgumentException::class, 'At least one platform is required.');

    $payload = new MultipartPayload;
    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        title: 'Post',
        scheduled_date: new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC')),
        first_comment_media: ['https://example.com/comment.jpg'],
    ))->addCommonTo($payload);

    $contents = array_column($payload->all(), 'contents', 'name');

    expect($contents['scheduled_date'])->toBe('2026-01-01T12:00:00+00:00')
        ->and($contents['first_comment_media[]'])->toBe('https://example.com/comment.jpg');
});
