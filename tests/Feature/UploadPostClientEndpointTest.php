<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommentActionData;
use Softgeng\UploadPost\Data\CommentQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\CreateCommentData;
use Softgeng\UploadPost\Data\DeleteCommentData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\HistoryQueryData;
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Data\Responses\PlatformMetricsResponse;
use Softgeng\UploadPost\Data\Responses\PostAnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\TotalImpressionsResponse;
use Softgeng\UploadPost\Data\ScheduledPostsQueryData;
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
            'selected_page_id' => '109876543210987',
            'selected_page_name' => 'My Business Page',
            'facebook_page_id' => '109876543210987',
            'facebook_page_name' => 'My Business Page',
            'linkedin_page_id' => '1234567890123456',
            'linkedin_page_name' => 'My Business Page',
            'boards' => [[
                'id' => '987654321098765432',
                'name' => 'Summer Recipes',
            ]],
            'locations' => [[
                'name' => 'accounts/123456789/locations/111111111',
                'title' => 'Main Street Store',
                'account_id' => 'accounts_123456789_111111111',
                'account_name' => 'Main Street Account',
            ]],
            'selected_location_id' => 'locations/111111111',
            'selected_location_name' => 'Main Street Store',
            'queue_settings' => [
                'timezone' => 'America/New_York',
                'slots' => [
                    ['hour' => 9, 'minute' => 0],
                    ['hour' => 12, 'minute' => 0],
                ],
                'days_of_week' => [0, 1, 2, 3, 4],
                'max_posts_per_slot' => 3,
                'full_slots' => ['2026-01-01T14:00:00+00:00'],
            ],
            'timezone' => 'America/New_York',
            'max_posts_per_slot' => 3,
            'slots' => [[
                'datetime_utc' => '2026-01-01T14:00:00+00:00',
                'datetime_local' => '2026-01-01T09:00:00-05:00',
                'available' => true,
                'post_count' => 0,
                'max_posts_per_slot' => 3,
                'is_full' => false,
                'manually_full' => false,
            ]],
            'next_available' => '2026-01-01T14:00:00+00:00',
            'full_slots' => ['2026-01-01T14:00:00+00:00'],
            'next_slot' => [
                'datetime_utc' => '2026-01-01T14:00:00+00:00',
                'datetime_local' => '2026-01-01T09:00:00-05:00',
                'timezone' => 'America/New_York',
            ],
            'pinterest_account_used' => 'pinterest_username',
            'success' => true,
            'preferences' => ['weekStartDay' => 1],
            'notifications' => [
                'channels' => ['webhook' => true, 'telegram' => false],
                'webhook_url' => 'https://example.com/webhook',
                'webhook_secret' => 'whsec_test',
                'webhook_events' => ['upload_completed' => true],
            ],
            'username' => 'profile',
            'profile' => ['username' => 'profile', 'platforms' => ['instagram']],
            'jwt' => 'jwt_123',
            'url' => 'https://connect.example.com',
            ...$payload,
        ], 200),
    ]);

    return new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);
}

/** @return array<string, mixed> */
function requestQuery(string $url): array
{
    $query = [];
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return $query;
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
        ->and($client->getHistory(page: 2, limit: 50)->history[0]->raw)->toBe(['id' => 1])
        ->and($client->getAnalytics('profile', new AnalyticsQueryData(platforms: [Platform::Instagram], days: 30))->get('status'))->toBe('ok')
        ->and($client->getTotalImpressions('profile', ['period' => 'last_week']))->toBeInstanceOf(TotalImpressionsResponse::class)
        ->and($client->getPostAnalytics('req_123', 'youtube'))->toBeInstanceOf(PostAnalyticsResponse::class)
        ->and($client->getPostAnalyticsByPlatformId('post_123', 'instagram', 'profile'))->toBeInstanceOf(PostAnalyticsResponse::class)
        ->and($client->getPlatformMetrics())->toBeInstanceOf(PlatformMetricsResponse::class)
        ->and($client->getMedia('instagram', 'profile', ['page_urn' => 'urn'])->media[0]->id)->toBe('1')
        ->and($client->getMedia('instagram', 'profile', ['platform' => 'attacker', 'user' => 'attacker'])->media[0]->id)->toBe('1');

    $client->getHistory(new HistoryQueryData(
        status: 'failed',
        external_id: 'external-123',
    ));

    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/status?request_id=req_123')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/analytics/profile?platforms=instagram')
            && str_contains((string) $request->url(), 'days=30')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/post-analytics/req_123?platform=youtube')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/media?platform=instagram')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/media?platform=instagram&user=profile')
            && ! str_contains((string) $request->url(), 'attacker')
    );
    $http->assertSent(
        fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/history')
            && requestQuery((string) $request->url()) === [
                'page' => '1',
                'limit' => '10',
                'status' => 'failed',
                'external_id' => 'external-123',
            ]
    );
});

it('calls scheduling and user endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);
    $scheduledEditDate = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+30 days')->format(DateTimeInterface::ATOM);

    expect($client->listScheduled()->scheduled_posts[0]->raw)->toBe(['id' => 1])
        ->and($client->listScheduled(new ScheduledPostsQueryData(
            profile_username: 'profile',
            from: '2026-01-01T00:00:00Z',
            limit: 10,
            offset: 20,
        ))->scheduled_posts[0]->raw)->toBe(['id' => 1])
        ->and($client->cancelScheduled('job_123')->get('status'))->toBe('ok')
        ->and($client->editScheduled('job_123', $scheduledEditDate, 'UTC', 'Updated title', 'Updated caption')->get('status'))->toBe('ok')
        ->and($client->listUsers()->profiles[0]->raw)->toBe(['id' => 1])
        ->and($client->getUser('profile')->profile?->username)->toBe('profile')
        ->and($client->getUser('profile')->profile?->raw['platforms'])->toBe(['instagram'])
        ->and($client->createUser('profile')->username)->toBe('profile')
        ->and($client->deleteUser('profile')->get('status'))->toBe('ok')
        ->and($client->generateJwt(new GenerateJwtData(username: 'profile', platforms: [Platform::X]))->jwt)->toBe('jwt_123')
        ->and($client->validateJwt('jwt_123')->profile?->username)->toBe('profile')
        ->and($client->getUserPreferences()->preferences?->week_start_day)->toBe(1)
        ->and($client->updateUserPreferences(['timezone' => 'UTC'])->get('status'))->toBe('ok')
        ->and($client->getNotificationConfig()->notifications?->webhook_url)->toBe('https://example.com/webhook')
        ->and($client->getNotificationConfig()->notifications?->webhook_secret)->toBe('whsec_test')
        ->and($client->updateNotificationConfig(['webhook_url' => 'https://example.com'])->notifications?->webhook_url)
        ->toBe('https://example.com/webhook')
        ->and($client->configureNotifications(new NotificationConfigData(
            webhook: true,
            telegram: false,
            webhook_url: 'https://example.com/webhook',
            webhook_events: ['upload_completed' => true],
        ))->notifications?->webhook_url)->toBe('https://example.com/webhook')
        ->and($client->configureWebhook('https://example.com/webhook', [
            WebhookEvent::SocialAccountConnected->value => false,
        ])->success)->toBeTrue();
    $client->deleteNotificationConfig();

    $http->assertSent(
        fn ($request): bool => $request->method() === 'PATCH'
            && str_contains((string) $request->url(), '/uploadposts/schedule/job_123')
            && $request['scheduled_date'] === $scheduledEditDate
            && $request['timezone'] === 'UTC'
            && $request['title'] === 'Updated title'
            && $request['caption'] === 'Updated caption'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/schedule')
            && requestQuery((string) $request->url()) === [
                'profile_username' => 'profile',
                'from' => '2026-01-01T00:00:00Z',
                'limit' => '10',
                'offset' => '20',
            ]
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST' && str_ends_with((string) $request->url(), '/uploadposts/users/generate-jwt')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_ends_with((string) $request->url(), '/uploadposts/users/validate-jwt')
            && $request->hasHeader('Authorization', 'Bearer jwt_123')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET' && str_ends_with((string) $request->url(), '/uploadposts/users/profile')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
            && $request['webhook_url'] === 'https://example.com'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
            && isset($request['channels']['webhook'])
            && $request['channels']['webhook'] === true
            && $request['webhook_url'] === 'https://example.com/webhook'
            && isset($request['webhook_events']['upload_completed'])
            && $request['webhook_events']['upload_completed'] === true
            && $request['webhook_events']['social_account_connected'] === true
            && $request['webhook_events']['social_account_disconnected'] === true
            && $request['webhook_events']['social_account_reauth_required'] === true
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/notifications')
            && isset($request['webhook_events']['social_account_connected'])
            && $request['webhook_events']['social_account_connected'] === false
    );
});

it('calls queue endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http);

    expect($client->getQueueSettings('profile')->queue_settings?->timezone)->toBe('America/New_York')
        ->and($client->updateQueueSettings('profile', [
            'profile_username' => 'attacker',
            'timezone' => 'Europe/Madrid',
            'slots' => [['hour' => 8, 'minute' => 30]],
            'days_of_week' => [0, 1, 2, 3, 4],
            'max_posts_per_slot' => 2,
        ])->success)->toBeTrue()
        ->and($client->getQueuePreview('profile', 5)->slots[0]->datetime_utc)->toBe('2026-01-01T14:00:00+00:00')
        ->and($client->getQueuePreview('profile', 5)->slots[0]->available)->toBeTrue()
        ->and($client->markQueueSlotFull('profile', '2026-01-01T14:00:00+00:00')->full_slots)->toBe(['2026-01-01T14:00:00+00:00'])
        ->and($client->unmarkQueueSlotFull('profile', '2026-01-01T14:00:00+00:00')->message)->toBe('done')
        ->and($client->getNextAvailableSlot('profile')->next_slot?->timezone)->toBe('America/New_York');

    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/queue/settings?profile_username=profile')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/queue/settings')
            && $request['profile_username'] === 'profile'
            && $request['timezone'] === 'Europe/Madrid'
            && $request['slots'] === [['hour' => 8, 'minute' => 30]]
            && $request['days_of_week'] === [0, 1, 2, 3, 4]
            && $request['max_posts_per_slot'] === 2
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/queue/preview?profile_username=profile&count=5')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/queue/slot-full')
            && $request['profile_username'] === 'profile'
            && $request['slot_datetime'] === '2026-01-01T14:00:00+00:00'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with((string) $request->url(), '/uploadposts/queue/slot-full')
            && $request['profile_username'] === 'profile'
            && $request['slot_datetime'] === '2026-01-01T14:00:00+00:00'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/queue/next-slot?profile_username=profile')
    );
});

it('validates queue request limits before sending', function (): void {
    $client = uploadPostClientWithFake(new HttpFactory);

    expect(fn (): mixed => $client->updateQueueSettings('profile', [
        'slots' => 'invalid',
    ]))->toThrow(InvalidArgumentException::class, 'slots must contain at most 24 entries.')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'slots' => ['invalid'],
        ]))->toThrow(InvalidArgumentException::class, 'Each queue slot must contain an hour from 0 to 23')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'days_of_week' => 'invalid',
        ]))->toThrow(InvalidArgumentException::class, 'days_of_week must be an array.')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'slots' => [['hour' => 24, 'minute' => 0]],
        ]))->toThrow(InvalidArgumentException::class, 'Each queue slot must contain an hour from 0 to 23')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'days_of_week' => [7],
        ]))->toThrow(InvalidArgumentException::class, 'days_of_week values must be between 0 and 6')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'max_posts_per_slot' => 101,
        ]))->toThrow(InvalidArgumentException::class, 'max_posts_per_slot must be between 1 and 100')
        ->and(fn (): mixed => $client->updateQueueSettings('profile', [
            'timezone' => 'Not/A_Timezone',
        ]))->toThrow(InvalidArgumentException::class, 'timezone must be a valid IANA timezone')
        ->and(fn (): mixed => $client->getQueuePreview('profile', 51))
        ->toThrow(InvalidArgumentException::class, 'count must be between 1 and 50');
});

it('validates scheduled edits and user week start preferences before sending', function (): void {
    $client = uploadPostClientWithFake(new HttpFactory);
    $future = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+1 day')->format(DateTimeInterface::ATOM);
    $tooFar = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+366 days')->format(DateTimeInterface::ATOM);

    expect(fn (): mixed => $client->editScheduled('job_123'))
        ->toThrow(InvalidArgumentException::class, 'At least one scheduled post field is required.')
        ->and(fn (): mixed => $client->editScheduled('job_123', title: 'Updated title'))
        ->not->toThrow(InvalidArgumentException::class)
        ->and(fn (): mixed => $client->editScheduled('job_123', 'not-a-date'))
        ->toThrow(InvalidArgumentException::class, 'scheduled_date must be a valid ISO 8601 date.')
        ->and(fn (): mixed => $client->editScheduled('job_123', '2026-01-01T25:00:00Z'))
        ->toThrow(InvalidArgumentException::class, 'scheduled_date must be a valid ISO 8601 date.')
        ->and(fn (): mixed => $client->editScheduled('job_123', '2020-01-01T00:00:00Z'))
        ->toThrow(InvalidArgumentException::class, 'scheduled_date must be in the future.')
        ->and(fn (): mixed => $client->editScheduled('job_123', $tooFar))
        ->toThrow(InvalidArgumentException::class, 'scheduled_date cannot be more than 365 days in the future.')
        ->and(fn (): mixed => $client->editScheduled('job_123', $future, 'Not/A_Timezone'))
        ->toThrow(InvalidArgumentException::class, 'timezone must be a valid IANA timezone.')
        ->and(fn (): mixed => $client->updateUserPreferences(['weekStartDay' => 1]))
        ->not->toThrow(InvalidArgumentException::class)
        ->and(fn (): mixed => $client->updateUserPreferences(['weekStartDay' => 2]))
        ->toThrow(InvalidArgumentException::class, 'weekStartDay must be 0 (Sunday) or 1 (Monday).');
});

it('validates platform resource identifiers before sending', function (): void {
    $client = uploadPostClientWithFake(new HttpFactory);

    expect(fn (): mixed => $client->selectFacebookPage('', 'profile'))
        ->toThrow(InvalidArgumentException::class, 'pageId and profileUsername are required.')
        ->and(fn (): mixed => $client->selectLinkedinPage('', 'profile'))
        ->toThrow(InvalidArgumentException::class, 'pageId and profileUsername are required.')
        ->and(fn (): mixed => $client->selectGoogleBusinessLocation('', 'profile'))
        ->toThrow(InvalidArgumentException::class, 'locationId and profileUsername are required.')
        ->and(fn (): mixed => $client->clearGoogleBusinessLocation(' '))
        ->toThrow(InvalidArgumentException::class, 'profileUsername is required.');
});

it('calls platform-aware comment and platform resource endpoints', function (): void {
    $http = new HttpFactory;
    $client = uploadPostClientWithFake($http, [
        'id' => 'comment_456',
        'platform' => 'tiktok',
        'action' => 'hide',
        'comment_id' => 'comment_123',
        'result' => ['comment_id' => 'comment_456'],
    ]);

    expect($client->getPostComments(new CommentQueryData(
        user: 'profile',
        platform: Platform::TikTok,
        post_id: 'post_123',
        limit: 20,
        comment_id: 'parent_123',
    ))->comments[0]->id)->toBe('1')
        ->and($client->createComment(new CreateCommentData(
            user: 'profile',
            message: 'Thanks',
            platform: Platform::TikTok,
            comment_id: 'comment_123',
            post_id: 'post_123',
        ))->result)->toBe(['comment_id' => 'comment_456'])
        ->and($client->deleteComment(new DeleteCommentData(
            user: 'profile',
            comment_id: 'comment_123',
            platform: Platform::LinkedIn,
            post_id: 'urn:li:ugcPost:123',
        ))->id)->toBe('comment_456')
        ->and($client->actOnComment(new CommentActionData(
            user: 'profile',
            action: 'hide',
            platform: Platform::TikTok,
            comment_id: 'comment_123',
            post_id: 'post_123',
        ))->action)->toBe('hide')
        ->and($client->getFacebookPages('profile')->pages[0]->name)->toBe('My Business Page')
        ->and($client->getFacebookPage('profile')->selected_page_id)->toBe('109876543210987')
        ->and($client->selectFacebookPage('109876543210987', 'profile')->facebook_page_id)->toBe('109876543210987')
        ->and($client->clearFacebookPage('profile')->get('status'))->toBe('ok')
        ->and($client->getLinkedinPages('profile')->pages[0]->account_id)->toBe('1234567890123456')
        ->and($client->getLinkedinPage('profile')->selected_page_name)->toBe('My Business Page')
        ->and($client->selectLinkedinPage('1234567890123456', 'profile')->linkedin_page_id)->toBe('1234567890123456')
        ->and($client->clearLinkedinPage('profile')->get('status'))->toBe('ok')
        ->and($client->getPinterestBoards('profile')->boards[0]->name)->toBe('Summer Recipes')
        ->and($client->getPinterestBoards('profile')->pinterest_account_used)->toBe('pinterest_username')
        ->and($client->getGoogleBusinessLocations('profile')->locations[0]->title)->toBe('Main Street Store')
        ->and($client->getGoogleBusinessLocations('profile')->locations[0]->account_name)->toBe('Main Street Account')
        ->and($client->getGoogleBusinessLocation('profile')->selected_location_id)->toBe('locations/111111111')
        ->and($client->getGoogleBusinessLocation('profile')->selected_location_name)->toBe('Main Street Store')
        ->and($client->selectGoogleBusinessLocation('location_123', 'profile')->get('status'))->toBe('ok')
        ->and($client->clearGoogleBusinessLocation('profile')->get('status'))->toBe('ok');

    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/comments?platform=tiktok&user=profile&post_id=post_123&limit=20&comment_id=parent_123')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'GET'
            && str_contains((string) $request->url(), '/uploadposts/users/google-business-location?profile_username=profile')
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/comments/create')
            && $request['platform'] === 'tiktok'
            && $request['post_id'] === 'post_123'
            && $request['comment_id'] === 'comment_123'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with((string) $request->url(), '/uploadposts/comments/delete')
            && $request['platform'] === 'linkedin'
            && $request['post_id'] === 'urn:li:ugcPost:123'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/comments/action')
            && $request['platform'] === 'tiktok'
            && $request['action'] === 'hide'
            && $request['post_id'] === 'post_123'
    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'POST'
            && str_ends_with((string) $request->url(), '/uploadposts/users/google-business-location')
            && $request['profile_username'] === 'profile'
            && $request['gbp_location_id'] === 'location_123'

    );
    $http->assertSent(
        fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with((string) $request->url(), '/uploadposts/users/google-business-location')
            && $request['profile_username'] === 'profile'
    );
});
