<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\Responses\ActionResponse;
use Softgeng\UploadPost\Data\Responses\CommentsResponse;
use Softgeng\UploadPost\Data\Responses\FacebookPagesResponse;
use Softgeng\UploadPost\Data\Responses\GenericResponse;
use Softgeng\UploadPost\Data\Responses\GoogleBusinessLocationsResponse;
use Softgeng\UploadPost\Data\Responses\HistoryResponse;
use Softgeng\UploadPost\Data\Responses\JwtResponse;
use Softgeng\UploadPost\Data\Responses\LinkedinPagesResponse;
use Softgeng\UploadPost\Data\Responses\ListResponse;
use Softgeng\UploadPost\Data\Responses\MediaResponse;
use Softgeng\UploadPost\Data\Responses\NotificationConfigResponse;
use Softgeng\UploadPost\Data\Responses\PinterestBoardsResponse;
use Softgeng\UploadPost\Data\Responses\ResourceListResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostsResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\Responses\UserProfilesResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Enums\WebhookEvent;
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

test('request DTOs can be created from arrays', function (): void {
    expect(AnalyticsQueryData::fromArray([
        'platforms' => [Platform::Instagram, 'youtube'],
        'page_id' => 123,
    ])->toQuery())->toBe([
        'platforms' => 'instagram,youtube',
        'page_id' => '123',
    ]);

    expect(GenerateJwtData::fromArray([
        'username' => 'profile',
        'platforms' => ['x'],
        'show_calendar' => 'true',
    ])->toArray())->toBe([
        'username' => 'profile',
        'platforms' => ['x'],
        'show_calendar' => true,
    ]);

    expect(NotificationConfigData::fromArray([
        'channels' => ['webhook' => 'true', 'telegram' => 'false'],
        'webhook_url' => 'https://example.com/webhook',
        'telegram_chat_id' => 123456789,
        'webhook_events' => [
            'upload_completed' => 'true',
            'social_account_connected' => 'false',
            '' => true,
            'ignored' => 'maybe',
        ],
    ])->toArray())->toBe([
        'channels' => ['webhook' => true, 'telegram' => false],
        'webhook_url' => 'https://example.com/webhook',
        'telegram_chat_id' => '123456789',
        'webhook_events' => [
            'upload_completed' => true,
            'social_account_connected' => false,
            'social_account_disconnected' => true,
            'social_account_reauth_required' => true,
        ],
    ]);

    $video = UploadVideoData::fromArray([
        'video' => 'https://example.com/video.mp4',
        'user' => 'profile',
        'platforms' => ['youtube'],
        'title' => 'Video',
        'async_upload' => 'true',
        'tags' => ['php'],
        'youtube_subtitles' => [
            ['language' => 'en', 'url' => 'https://example.com/subtitles.vtt'],
        ],
        'idempotency_key' => 'idem-video',
    ]);
    $videoContents = array_column($video->toMultipart()->all(), 'contents', 'name');

    expect($video->idempotency_key)->toBe('idem-video')
        ->and($video->common->async_upload)->toBeTrue()
        ->and($videoContents['tags[]'])->toBe('php')
        ->and($videoContents['youtube_subtitle_language_0'])->toBe('en');

    $text = UploadTextData::fromArray([
        'common' => ['user' => 'profile', 'platforms' => ['x'], 'title' => 'Text'],
        'link_url' => 'https://example.com',
        'options' => ['poll_options' => ['yes', 'no'], 'poll_duration' => '60'],
    ]);

    expect($text->common->title)->toBe('Text')
        ->and($text->options->poll_options)->toBe(['yes', 'no'])
        ->and($text->options->poll_duration)->toBe('60');

    expect(UploadPhotosData::fromArray([
        'photos' => ['https://example.com/photo.jpg'],
        'common' => new CommonUploadData(user: 'profile', platforms: ['instagram']),
        'options' => new PlatformOptions(media_type: 'IMAGE'),
    ])->options->media_type)->toBe('IMAGE');

    expect(UploadDocumentData::fromArray([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'add_to_queue' => 'false',
        'max_posts_per_slot' => '2',
    ])->max_posts_per_slot)->toBe(2);
});

test('request DTOs can be converted to arrays', function (): void {
    $video = new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::YouTube],
            title: 'Video',
            scheduled_date: new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC')),
            async_upload: false,
        ),
        options: new PlatformOptions(
            tags: ['php'],
            embeddable: false,
            youtube_subtitles: [
                new YoutubeSubtitleData(language: 'en', url: 'https://example.com/subtitles.vtt'),
            ],
        ),
        idempotency_key: 'idem-video',
    );

    expect($video->toArray())->toBe([
        'video' => 'https://example.com/video.mp4',
        'common' => [
            'user' => 'profile',
            'platforms' => ['youtube'],
            'title' => 'Video',
            'scheduled_date' => '2026-01-01T12:00:00+00:00',
            'async_upload' => false,
        ],
        'options' => [
            'tags' => ['php'],
            'embeddable' => false,
            'youtube_subtitles' => [
                ['language' => 'en', 'url' => 'https://example.com/subtitles.vtt'],
            ],
        ],
        'idempotency_key' => 'idem-video',
    ]);

    expect(UploadTextData::fromArray([
        'common' => ['user' => 'profile', 'platforms' => ['x']],
        'options' => ['x_long_text_as_post' => true],
    ])->toArray())->toBe([
        'common' => ['user' => 'profile', 'platforms' => ['x']],
        'options' => ['x_long_text_as_post' => true],
    ]);

    expect(UploadDocumentData::fromArray([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'add_to_queue' => 'false',
    ])->toArray())->toBe([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'add_to_queue' => false,
    ]);

    expect(NotificationConfigData::webhook('https://example.com/webhook')->toArray())->toBe([
        'channels' => ['webhook' => true, 'telegram' => false],
        'webhook_url' => 'https://example.com/webhook',
        'webhook_events' => [
            'upload_completed' => true,
            'social_account_connected' => true,
            'social_account_disconnected' => true,
            'social_account_reauth_required' => true,
        ],
    ])->and(NotificationConfigData::webhook('https://example.com/webhook', [
        WebhookEvent::SocialAccountReauthRequired->value => false,
        WebhookEvent::UploadCompleted,
    ])->toArray())->toBe([
        'channels' => ['webhook' => true, 'telegram' => false],
        'webhook_url' => 'https://example.com/webhook',
        'webhook_events' => [
            'upload_completed' => true,
            'social_account_connected' => true,
            'social_account_disconnected' => true,
            'social_account_reauth_required' => false,
        ],
    ]);

    expect((new NotificationConfigData(
        webhook: true,
        webhook_url: 'https://example.com/webhook',
        webhook_events: [
            'upload_completed' => false,
            'unknown_event' => true,
            WebhookEvent::SocialAccountConnected,
        ],
    ))->toArray())->toBe([
        'channels' => ['webhook' => true],
        'webhook_url' => 'https://example.com/webhook',
        'webhook_events' => [
            'upload_completed' => false,
            'social_account_connected' => true,
            'social_account_disconnected' => true,
            'social_account_reauth_required' => true,
        ],
    ]);
});

test('request DTO array factories cover defensive branches', function (): void {
    expect(AnalyticsQueryData::fromArray([
        'platforms' => [123],
        'page_urn' => 'urn:li:page:123',
    ])->toArray())->toBe([
        'platforms' => ['123'],
        'page_urn' => 'urn:li:page:123',
    ])->and(AnalyticsQueryData::fromArray(['platforms' => ''])->toArray())->toBe([]);

    expect(PlatformOptions::empty()->toArray())->toBe([]);

    expect(NotificationConfigData::fromArray([
        'webhook_events' => 'upload_completed',
    ])->toArray())->toBe([])
        ->and(NotificationConfigData::fromArray([
            'webhook_events' => ['upload_completed'],
        ])->toArray())->toBe([
            'webhook_events' => ['upload_completed' => true],
        ])
        ->and(NotificationConfigData::fromArray([
            'webhook_events' => [true],
        ])->toArray())->toBe([]);

    $photos = UploadPhotosData::fromArray([
        'photo' => 'https://example.com/photo.jpg',
        'user' => 'profile',
        'platforms' => 'instagram',
        'idempotency_key' => 'idem-photo',
    ]);

    expect($photos->toArray())->toBe([
        'photos' => ['https://example.com/photo.jpg'],
        'common' => ['user' => 'profile', 'platforms' => ['instagram']],
        'idempotency_key' => 'idem-photo',
    ]);

    expect(fn (): UploadPhotosData => new UploadPhotosData(
        photos: [],
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram]),
    ))->toThrow(InvalidArgumentException::class, 'At least one photo is required.');

    expect(fn (): UploadDocumentData => new UploadDocumentData(
        document: 'https://example.com/document.pdf',
        user: '',
        title: 'Document',
    ))->toThrow(InvalidArgumentException::class, 'user is required.')
        ->and(fn (): UploadDocumentData => new UploadDocumentData(
            document: 'https://example.com/document.pdf',
            user: 'profile',
            title: '',
        ))->toThrow(InvalidArgumentException::class, 'title is required.');

    $document = UploadDocumentData::fromArray([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'scheduled_date' => 123,
    ]);
    $contents = array_column($document->toMultipart()->all(), 'contents', 'name');

    expect($document->toArray()['scheduled_date'])->toBe('123')
        ->and($contents['scheduled_date'])->toBe('123');

    $datedDocument = new UploadDocumentData(
        document: 'https://example.com/document.pdf',
        user: 'profile',
        title: 'Document',
        scheduled_date: new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC')),
    );
    $datedContents = array_column($datedDocument->toMultipart()->all(), 'contents', 'name');

    expect($datedContents['scheduled_date'])->toBe('2026-01-01T12:00:00+00:00');
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
    $jwtFromApi = JwtResponse::fromArray(['success' => true, 'access_url' => 'https://connect.example.com/new', 'duration' => '36h']);
    $listFromData = ListResponse::fromArray(['data' => [['id' => 1]]]);
    $listFromItems = ListResponse::fromArray(['items' => [['id' => 2]]]);
    $listFromRawList = ListResponse::fromArray([['id' => 3]]);
    $status = StatusResponse::fromArray([
        'status' => 'done',
        'request_id' => 123,
        'job_id' => 'job',
        'completed' => '1',
        'total' => 2,
        'results' => [['platform' => 'x']],
        'last_update' => '2026-01-01T00:00:00Z',
    ]);
    $upload = UploadResponse::fromArray(['success' => true, 'request_id' => 123, 'job_id' => 456, 'status' => 'queued', 'message' => 'ok', 'results' => ['linkedin' => ['id' => 1]]]);
    $user = UserResponse::fromArray(['success' => true, 'profile' => ['username' => 'profile']]);
    $history = HistoryResponse::fromArray(['history' => [['id' => 1]], 'total' => '1', 'page' => '2', 'limit' => '50']);
    $scheduled = ScheduledPostsResponse::fromArray(['scheduled_posts' => [['job_id' => 'job']]]);
    $scheduledPost = ScheduledPostResponse::fromArray(['success' => true, 'job_id' => 'job', 'scheduled_date' => '2026-01-01T00:00:00Z', 'title' => 'Title', 'caption' => 'Caption']);
    $resources = ResourceListResponse::fromArray(['success' => true, 'boards' => [['id' => 'board']], 'pinterest_account_used' => 'pin'], 'boards');
    $facebookPages = FacebookPagesResponse::fromArray(['success' => true, 'pages' => [['id' => 'facebook-page']]]);
    $linkedinPages = LinkedinPagesResponse::fromArray(['success' => true, 'pages' => [['id' => 'linkedin-page', 'vanityName' => 'company']]]);
    $pinterestBoards = PinterestBoardsResponse::fromArray(['success' => true, 'boards' => [['id' => 'board']], 'pinterest_account_used' => 'pin']);
    $googleBusinessLocations = GoogleBusinessLocationsResponse::fromArray(['success' => true, 'locations' => [['name' => 'accounts/1/locations/2']]]);
    $users = UserProfilesResponse::fromArray(['success' => true, 'profiles' => [['username' => 'profile']], 'limit' => '5', 'plan' => 'pro']);
    $notifications = NotificationConfigResponse::fromArray([
        'success' => true,
        'notifications' => ['webhook_url' => 'https://example.com/webhook'],
    ]);

    expect($generic->get('nested.value'))->toBe('ok')
        ->and($generic->toArray())->toBe(['nested' => ['value' => 'ok']])
        ->and($jwt->jwt)->toBe('jwt-token')
        ->and($jwt->url)->toBe('https://connect.example.com')
        ->and($jwtFromApi->success)->toBeTrue()
        ->and($jwtFromApi->url)->toBe('https://connect.example.com/new')
        ->and($jwtFromApi->duration)->toBe('36h')
        ->and($listFromData->items)->toBe([['id' => 1]])
        ->and($listFromItems->items)->toBe([['id' => 2]])
        ->and($listFromRawList->items)->toBe([['id' => 3]])
        ->and($status->status)->toBe('done')
        ->and($status->request_id)->toBe('123')
        ->and($status->job_id)->toBe('job')
        ->and($status->completed)->toBe(1)
        ->and($status->total)->toBe(2)
        ->and($status->results)->toBe([['platform' => 'x']])
        ->and($status->last_update)->toBe('2026-01-01T00:00:00Z')
        ->and($upload->request_id)->toBe('123')
        ->and($upload->job_id)->toBe('456')
        ->and($upload->status)->toBe('queued')
        ->and($upload->message)->toBe('ok')
        ->and($upload->success)->toBeTrue()
        ->and($upload->results)->toBe(['linkedin' => ['id' => 1]])
        ->and($user->username)->toBe('profile')
        ->and($user->success)->toBeTrue()
        ->and($user->profile)->toBe(['username' => 'profile'])
        ->and($history->history)->toBe([['id' => 1]])
        ->and($history->items)->toBe([['id' => 1]])
        ->and($history->total)->toBe(1)
        ->and($scheduled->scheduled_posts)->toBe([['job_id' => 'job']])
        ->and($scheduled->items)->toBe([['job_id' => 'job']])
        ->and($scheduledPost->job_id)->toBe('job')
        ->and($scheduledPost->caption)->toBe('Caption')
        ->and($resources->items)->toBe([['id' => 'board']])
        ->and($resources->pinterest_account_used)->toBe('pin')
        ->and($facebookPages->pages)->toBe([['id' => 'facebook-page']])
        ->and($facebookPages->items)->toBe([['id' => 'facebook-page']])
        ->and($linkedinPages->pages)->toBe([['id' => 'linkedin-page', 'vanityName' => 'company']])
        ->and($linkedinPages->items)->toBe([['id' => 'linkedin-page', 'vanityName' => 'company']])
        ->and($pinterestBoards->boards)->toBe([['id' => 'board']])
        ->and($pinterestBoards->items)->toBe([['id' => 'board']])
        ->and($pinterestBoards->pinterest_account_used)->toBe('pin')
        ->and($googleBusinessLocations->locations)->toBe([['name' => 'accounts/1/locations/2']])
        ->and($googleBusinessLocations->items)->toBe([['name' => 'accounts/1/locations/2']])
        ->and($users->profiles)->toBe([['username' => 'profile']])
        ->and($users->items)->toBe([['username' => 'profile']])
        ->and($users->limit)->toBe(5)
        ->and($notifications->success)->toBeTrue()
        ->and($notifications->notifications)->toBe(['webhook_url' => 'https://example.com/webhook']);
});

test('response DTOs convert empty values to null', function (): void {
    expect(JwtResponse::fromArray(['jwt' => '', 'url' => ''])->jwt)->toBeNull()
        ->and(JwtResponse::fromArray(['jwt' => '', 'url' => ''])->url)->toBeNull()
        ->and(StatusResponse::fromArray(['status' => '', 'request_id' => '', 'job_id' => ''])->status)->toBeNull()
        ->and(UploadResponse::fromArray(['request_id' => '', 'job_id' => '', 'status' => '', 'message' => ''])->request_id)->toBeNull()
        ->and(UserResponse::fromArray(['username' => ''])->username)->toBeNull();
});

test('response DTOs cover fallback accessors and scalar booleans', function (): void {
    $reply = ActionResponse::fromArray([
        'success' => 'true',
        'recipient_id' => 123,
        'message_id' => 'mid',
    ]);

    expect($reply->success)->toBeTrue()
        ->and($reply->recipient_id)->toBe('123')
        ->and($reply->message_id)->toBe('mid')
        ->and(CommentsResponse::fromArray(['comments' => []])->missing)->toBeNull()
        ->and(FacebookPagesResponse::fromArray(['pages' => []])->missing)->toBeNull()
        ->and(GoogleBusinessLocationsResponse::fromArray(['locations' => []])->missing)->toBeNull()
        ->and(HistoryResponse::fromArray(['history' => []])->missing)->toBeNull()
        ->and(LinkedinPagesResponse::fromArray(['pages' => []])->missing)->toBeNull()
        ->and(MediaResponse::fromArray(['media' => []])->missing)->toBeNull()
        ->and(PinterestBoardsResponse::fromArray(['boards' => []])->missing)->toBeNull()
        ->and(ScheduledPostsResponse::fromArray(['scheduled_posts' => []])->missing)->toBeNull()
        ->and(UserProfilesResponse::fromArray(['profiles' => []])->missing)->toBeNull();
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
