<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\UploadPostClient;

function uploadPostClientWithFake(HttpFactory $http, array $payload = []): UploadPostClient
{
    $http->fake([
        '*' => $http->response([
            'request_id' => 'req_123',
            'job_id' => 'job_123',
            'status' => 'ok',
            'message' => 'done',
            'data' => [['id' => 1]],
            'username' => 'profile',
            'jwt' => 'jwt_123',
            'url' => 'https://connect.example.com',
            ...$payload,
        ], 200),
    ]);

    return new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);
}

it('calls upload endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    $common = new CommonUploadData(user: 'profile', platforms: [Platform::TikTok], title: 'Post');

    expect($client->uploadVideo(new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: $common,
    ))->request_id)->toBe('req_123')
        ->and($client->uploadPhotos(new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: $common,
        ))->request_id)->toBe('req_123')
        ->and($client->uploadDocument(new UploadDocumentData(
            document: 'https://example.com/deck.pdf',
            user: 'profile',
            title: 'Deck',
        ))->request_id)->toBe('req_123');

    $http->assertSent(fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/upload'));
    $http->assertSent(fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/upload_photos'));
    $http->assertSent(fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/upload_document'));
});

it('calls status history analytics and media endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    expect($client->getStatus('req_123')->request_id)->toBe('req_123')
        ->and($client->getJobStatus('job_123')->job_id)->toBe('job_123')
        ->and($client->getHistory(page: 2, limit: 50)->items)->toBe([['id' => 1]])
        ->and($client->getAnalytics('profile', new AnalyticsQueryData(platforms: [Platform::Instagram]))->get('status'))->toBe('ok')
        ->and($client->getTotalImpressions('profile', ['period' => 'last_week'])->get('status'))->toBe('ok')
        ->and($client->getPostAnalytics('req_123')->get('status'))->toBe('ok')
        ->and($client->getPostAnalyticsByPlatformId('post_123', 'instagram', 'profile')->get('status'))->toBe('ok')
        ->and($client->getPlatformMetrics()->get('status'))->toBe('ok')
        ->and($client->getMedia('instagram', 'profile', ['page_urn' => 'urn'])->items)->toBe([['id' => 1]]);

    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/status?request_id=req_123')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/analytics/profile?platforms=instagram')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/media?platform=instagram')
    );
});

it('calls scheduling and user endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    expect($client->listScheduled()->items)->toBe([['id' => 1]])
        ->and($client->cancelScheduled('job_123')->get('status'))->toBe('ok')
        ->and($client->editScheduled('job_123', '2026-01-01T00:00:00Z', 'UTC')->get('status'))->toBe('ok')
        ->and($client->listUsers()->items)->toBe([['id' => 1]])
        ->and($client->createUser('profile')->username)->toBe('profile')
        ->and($client->deleteUser('profile')->get('status'))->toBe('ok')
        ->and($client->generateJwt(new GenerateJwtData(username: 'profile', platforms: [Platform::X]))->jwt)->toBe('jwt_123')
        ->and($client->validateJwt('jwt_123')->get('status'))->toBe('ok')
        ->and($client->getUserPreferences()->get('status'))->toBe('ok')
        ->and($client->updateUserPreferences(['timezone' => 'UTC'])->get('status'))->toBe('ok')
        ->and($client->getNotificationConfig()->get('status'))->toBe('ok')
        ->and($client->updateNotificationConfig(['webhook_url' => 'https://example.com'])->get('status'))->toBe('ok');

    $http->assertSent(
        fn ($request): bool => $request->method() === 'PATCH' && str_contains((string) $request->url(), '/uploadposts/schedule/job_123')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/uploadposts/users/generate-jwt')
    );
});

it('calls comment and platform resource endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    expect($client->getPostComments('profile', ['post_id' => 'post_123'])->items)->toBe([['id' => 1]])
        ->and($client->replyToComment('profile', 'comment_123', 'Thanks')->get('status'))->toBe('ok')
        ->and($client->publicReplyToComment('profile', 'comment_123', 'Thanks')->get('status'))->toBe('ok')
        ->and($client->getFacebookPages('profile')->items)->toBe([['id' => 1]])
        ->and($client->getLinkedinPages('profile')->items)->toBe([['id' => 1]])
        ->and($client->getPinterestBoards('profile')->items)->toBe([['id' => 1]])
        ->and($client->getGoogleBusinessLocations('profile')->items)->toBe([['id' => 1]])
        ->and($client->selectGoogleBusinessLocation('location_123', 'profile')->get('status'))->toBe('ok');

    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/comments?platform=instagram')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/uploadposts/google-business/locations/select')

    );
});
