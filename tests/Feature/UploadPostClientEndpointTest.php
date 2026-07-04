<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Enums\WebhookEvent;
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
            'pages' => [[
                'id' => '109876543210987',
                'name' => 'My Business Page',
                'picture' => 'https://url.to/profile/picture.jpg',
                'account_id' => '1234567890123456',
            ]],
            'boards' => [[
                'id' => '987654321098765432',
                'name' => 'Summer Recipes',
            ]],
            'locations' => [[
                'name' => 'accounts/123456789/locations/111111111',
                'title' => 'Main Street Store',
                'account_id' => 'accounts_123456789_111111111',
            ]],
            'pinterest_account_used' => 'pinterest_username',
            'success' => true,
            'notifications' => ['webhook_url' => 'https://example.com/webhook'],
            'username' => 'profile',
            'profile' => ['username' => 'profile', 'platforms' => ['instagram']],
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
        ->and($client->getUser('profile')->profile)->toBe(['username' => 'profile', 'platforms' => ['instagram']])
        ->and($client->createUser('profile')->username)->toBe('profile')
        ->and($client->deleteUser('profile')->get('status'))->toBe('ok')
        ->and($client->generateJwt(new GenerateJwtData(username: 'profile', platforms: [Platform::X]))->jwt)->toBe('jwt_123')
        ->and($client->validateJwt('jwt_123')->get('status'))->toBe('ok')
        ->and($client->getUserPreferences()->get('status'))->toBe('ok')
        ->and($client->updateUserPreferences(['timezone' => 'UTC'])->get('status'))->toBe('ok')
        ->and($client->getNotificationConfig()->get('status'))->toBe('ok')
        ->and($client->updateNotificationConfig(['webhook_url' => 'https://example.com'])->get('status'))->toBe('ok')
        ->and($client->configureNotifications(new NotificationConfigData(
            webhook: true,
            telegram: false,
            webhook_url: 'https://example.com/webhook',
            webhook_events: ['upload_completed' => true],
        ))->notifications)->toBe(['webhook_url' => 'https://example.com/webhook'])
        ->and($client->configureWebhook('https://example.com/webhook', [
            WebhookEvent::SocialAccountConnected->value => false,
        ])->success)->toBeTrue();

    $http->assertSent(
        fn ($request): bool => $request->method() === 'PATCH' && str_contains((string) $request->url(), '/uploadposts/schedule/job_123')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/uploadposts/users/generate-jwt')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET' && str_ends_with((string) $request->url(), '/uploadposts/users/profile')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
            && $request['channels']['webhook'] === true
            && $request['webhook_url'] === 'https://example.com/webhook'
            && $request['webhook_events']['upload_completed'] === true
            && $request['webhook_events']['social_account_connected'] === true
            && $request['webhook_events']['social_account_disconnected'] === true
            && $request['webhook_events']['social_account_reauth_required'] === true
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
            && $request['webhook_events']['social_account_connected'] === false
    );
});

it('calls comment and platform resource endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    expect($client->getPostComments('profile', ['post_id' => 'post_123'])->items)->toBe([['id' => 1]])
        ->and($client->replyToComment('profile', 'comment_123', 'Thanks')->get('status'))->toBe('ok')
        ->and($client->publicReplyToComment('profile', 'comment_123', 'Thanks')->get('status'))->toBe('ok')
        ->and($client->getFacebookPages('profile')->pages)->toBe([[
            'id' => '109876543210987',
            'name' => 'My Business Page',
            'picture' => 'https://url.to/profile/picture.jpg',
            'account_id' => '1234567890123456',
        ]])
        ->and($client->getFacebookPages('profile')->items)->toBe($client->getFacebookPages('profile')->pages)
        ->and($client->getLinkedinPages('profile')->pages)->toBe([[
            'id' => '109876543210987',
            'name' => 'My Business Page',
            'picture' => 'https://url.to/profile/picture.jpg',
            'account_id' => '1234567890123456',
        ]])
        ->and($client->getPinterestBoards('profile')->boards)->toBe([[
            'id' => '987654321098765432',
            'name' => 'Summer Recipes',
        ]])
        ->and($client->getPinterestBoards('profile')->pinterest_account_used)->toBe('pinterest_username')
        ->and($client->getPinterestBoards('profile')->items)->toBe($client->getPinterestBoards('profile')->boards)
        ->and($client->getGoogleBusinessLocations('profile')->locations)->toBe([[
            'name' => 'accounts/123456789/locations/111111111',
            'title' => 'Main Street Store',
            'account_id' => 'accounts_123456789_111111111',
        ]])
        ->and($client->getGoogleBusinessLocations('profile')->items)->toBe($client->getGoogleBusinessLocations('profile')->locations)
        ->and($client->selectGoogleBusinessLocation('location_123', 'profile')->get('status'))->toBe('ok');

    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/comments?platform=instagram')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/uploadposts/google-business/locations/select')

    );
});
