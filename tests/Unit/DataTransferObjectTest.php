<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommentActionData;
use Softgeng\UploadPost\Data\CommentQueryData;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\CreateCommentData;
use Softgeng\UploadPost\Data\DeleteCommentData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\HistoryQueryData;
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
use Softgeng\UploadPost\Data\Responses\PlatformMetricsResponse;
use Softgeng\UploadPost\Data\Responses\PostAnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\QueueNextSlotResponse;
use Softgeng\UploadPost\Data\Responses\QueuePreviewResponse;
use Softgeng\UploadPost\Data\Responses\QueueSettingsResponse;
use Softgeng\UploadPost\Data\Responses\QueueSlotFullResponse;
use Softgeng\UploadPost\Data\Responses\ResourceListResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostsResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\TotalImpressionsResponse;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\Responses\UserPreferencesResponse;
use Softgeng\UploadPost\Data\Responses\UserProfilesResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
use Softgeng\UploadPost\Data\ScheduledPostsQueryData;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
use Softgeng\UploadPost\Enums\GoogleBusinessCtaType;
use Softgeng\UploadPost\Enums\GoogleBusinessMediaCategory;
use Softgeng\UploadPost\Enums\GoogleBusinessPostType;
use Softgeng\UploadPost\Enums\GoogleBusinessTopicType;
use Softgeng\UploadPost\Enums\JwtLanguage;
use Softgeng\UploadPost\Enums\LinkedinPollDuration;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Enums\WebhookEvent;
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

test('analytics query data validates and serializes the Facebook days filter', function (): void {
    $query = new AnalyticsQueryData(platforms: [Platform::Facebook], days: 30);

    expect($query->toArray()['days'])->toBe(30)
        ->and($query->toQuery()['days'])->toBe('30')
        ->and(AnalyticsQueryData::fromArray(['platforms' => ['facebook'], 'days' => '7'])->days)->toBe(7)
        ->and(fn (): AnalyticsQueryData => new AnalyticsQueryData(days: 0))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): AnalyticsQueryData => new AnalyticsQueryData(days: 366))
        ->toThrow(InvalidArgumentException::class);
});

test('analytics query data drops empty optional values', function (): void {
    expect((new AnalyticsQueryData)->toQuery())->toBe([])
        ->and((new AnalyticsQueryData(platforms: ['', '   ']))->toQuery())->toBe([]);
});

test('history and scheduled query data serialize documented filters', function (): void {
    expect((new HistoryQueryData)->toQuery())->toBe([
        'page' => '1',
        'limit' => '10',
    ])->and((new HistoryQueryData(
        page: 2,
        limit: 50,
        platform: Platform::Reddit,
        status: 'failed',
        profile_username: 'profile',
        request_id: 'request-123',
        job_id: 'job-123',
        external_id: 'external-123',
        start: '2026-01-01',
        end: '2026-01-31',
    ))->toQuery())->toBe([
        'page' => '2',
        'limit' => '50',
        'platform' => 'reddit',
        'status' => 'failed',
        'profile_username' => 'profile',
        'request_id' => 'request-123',
        'job_id' => 'job-123',
        'external_id' => 'external-123',
        'start' => '2026-01-01',
        'end' => '2026-01-31',
    ])->and((new ScheduledPostsQueryData(
        profile_username: 'profile',
        from: '2026-01-01T00:00:00Z',
        to: '2026-02-01T00:00:00Z',
        limit: 20,
        offset: 40,
    ))->toQuery())->toBe([
        'profile_username' => 'profile',
        'from' => '2026-01-01T00:00:00Z',
        'to' => '2026-02-01T00:00:00Z',
        'limit' => '20',
        'offset' => '40',
    ]);
});

test('history and scheduled query data normalize arrays and reject invalid pagination', function (): void {
    expect(HistoryQueryData::fromArray([
        'page' => '3',
        'limit' => '50',
        'platform' => 'youtube',
        'profile' => 'profile',
    ])->toQuery())->toMatchArray([
        'page' => '3',
        'limit' => '50',
        'platform' => 'youtube',
        'profile_username' => 'profile',
    ])->and(ScheduledPostsQueryData::fromArray([
        'limit' => '10',
        'offset' => '5',
    ])->toQuery())->toBe([
        'limit' => '10',
        'offset' => '5',
    ])->and((new ScheduledPostsQueryData)->toQuery())->toBe([])
        ->and(fn (): HistoryQueryData => new HistoryQueryData(page: 0))
        ->toThrow(InvalidArgumentException::class, 'page must be at least 1.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(limit: 0))
        ->toThrow(InvalidArgumentException::class, 'limit must be at least 1.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(limit: 5))
        ->toThrow(InvalidArgumentException::class, 'limit must be one of 10, 20, 50, or 100.')
        ->and(fn (): ScheduledPostsQueryData => new ScheduledPostsQueryData(limit: 0))
        ->toThrow(InvalidArgumentException::class, 'limit must be at least 1.')
        ->and(fn (): ScheduledPostsQueryData => new ScheduledPostsQueryData(offset: -1))
        ->toThrow(InvalidArgumentException::class, 'offset cannot be negative.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(start: '2026-01-01'))
        ->toThrow(InvalidArgumentException::class, 'start and end must be provided together.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(start: '2026-01-01', end: '2026-04-01'))
        ->toThrow(InvalidArgumentException::class, 'cannot exceed 2 months.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(request_id: str_repeat('x', 201)))
        ->toThrow(InvalidArgumentException::class, 'request_id must be 200 characters or fewer')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(job_id: "job\n123"))
        ->toThrow(InvalidArgumentException::class, 'job_id must be 200 characters or fewer');
});

test('comment request DTOs serialize documented platform-aware payloads', function (): void {
    expect((new CommentQueryData(
        user: 'profile',
        platform: Platform::Bluesky,
        post_url: 'at://did:plc:example/app.bsky.feed.post/abc',
        limit: 50,
        after: 'cursor',
    ))->toQuery())->toBe([
        'platform' => 'bluesky',
        'user' => 'profile',
        'post_url' => 'at://did:plc:example/app.bsky.feed.post/abc',
        'limit' => '50',
        'after' => 'cursor',
    ])
        ->and((new CreateCommentData(
            user: 'profile',
            message: 'Nice post',
            platform: Platform::Facebook,
            post_id: 'post_123',
            attachment_url: 'https://example.com/image.jpg',
        ))->toArray())->toBe([
            'platform' => 'facebook',
            'user' => 'profile',
            'message' => 'Nice post',
            'post_id' => 'post_123',
            'attachment_url' => 'https://example.com/image.jpg',
        ])
        ->and((new DeleteCommentData(
            user: 'profile',
            comment_id: 'urn:li:comment:(urn:li:ugcPost:123,456)',
            platform: Platform::LinkedIn,
            post_id: 'urn:li:ugcPost:123',
        ))->toArray())->toBe([
            'platform' => 'linkedin',
            'user' => 'profile',
            'comment_id' => 'urn:li:comment:(urn:li:ugcPost:123,456)',
            'post_id' => 'urn:li:ugcPost:123',
        ])
        ->and((new DeleteCommentData(
            user: 'profile',
            comment_id: 'x-comment-123',
            platform: Platform::X,
        ))->toArray())->toBe([
            'platform' => 'x',
            'user' => 'profile',
            'comment_id' => 'x-comment-123',
        ])
        ->and((new DeleteCommentData(
            user: 'profile',
            comment_id: 'at://did:plc:example/app.bsky.feed.post/comment-123',
            platform: Platform::Bluesky,
        ))->toArray())->toBe([
            'platform' => 'bluesky',
            'user' => 'profile',
            'comment_id' => 'at://did:plc:example/app.bsky.feed.post/comment-123',
        ])
        ->and((new CommentActionData(
            user: 'profile',
            action: 'hide',
            platform: Platform::YouTube,
            comment_id: 'comment_123',
            post_id: 'video_123',
            ban_author: true,
        ))->toArray())->toBe([
            'platform' => 'youtube',
            'user' => 'profile',
            'action' => 'hide',
            'comment_id' => 'comment_123',
            'post_id' => 'video_123',
            'ban_author' => true,
        ]);
});

test('comment request DTOs cover supported platform variants and array factories', function (): void {
    expect((new CommentActionData(
        user: 'profile',
        action: 'enable_comments',
        platform: Platform::Instagram,
        post_id: 'post_123',
    ))->toArray()['post_id'])->toBe('post_123')
        ->and((new CommentActionData(
            user: 'profile',
            action: 'edit',
            platform: Platform::Facebook,
            comment_id: 'comment_123',
            message: 'Updated comment',
        ))->toArray()['message'])->toBe('Updated comment')
        ->and((new CommentActionData(
            user: 'profile',
            action: 'approve',
            platform: Platform::Threads,
            comment_id: 'comment_123',
        ))->toArray()['action'])->toBe('approve')
        ->and((new CommentActionData(
            user: 'profile',
            action: 'like',
            platform: Platform::TikTok,
            comment_id: 'comment_123',
        ))->toArray()['action'])->toBe('like')
        ->and(CommentQueryData::fromArray([
            'user' => 123,
            'platform' => Platform::YouTube,
            'post_id' => 456,
            'limit' => '20',
            'after' => 789,
            'comment_id' => 101,
        ])->toQuery())->toBe([
            'platform' => 'youtube',
            'user' => '123',
            'post_id' => '456',
            'limit' => '20',
            'after' => '789',
            'comment_id' => '101',
        ])
        ->and(CreateCommentData::fromArray([
            'user' => 123,
            'message' => 456,
            'platform' => 'facebook',
            'post_url' => 'https://facebook.example/post',
            'attachment_share_url' => 'https://facebook.example/animation.gif',
        ])->toArray())->toBe([
            'platform' => 'facebook',
            'user' => '123',
            'message' => '456',
            'post_url' => 'https://facebook.example/post',
            'attachment_share_url' => 'https://facebook.example/animation.gif',
        ])
        ->and(DeleteCommentData::fromArray([
            'user' => 123,
            'comment_id' => 456,
            'platform' => 'youtube',
        ])->toArray())->toBe([
            'platform' => 'youtube',
            'user' => '123',
            'comment_id' => '456',
        ])
        ->and(CommentActionData::fromArray([
            'user' => 123,
            'action' => 'hide',
            'platform' => 'youtube',
            'comment_id' => 456,
            'post_id' => 789,
            'ban_author' => 'true',
        ])->toArray())->toBe([
            'platform' => 'youtube',
            'user' => '123',
            'action' => 'hide',
            'comment_id' => '456',
            'post_id' => '789',
            'ban_author' => true,
        ]);
});

test('comment request DTOs enforce endpoint target and platform rules', function (): void {
    expect(fn (): CommentQueryData => new CommentQueryData(user: 'profile'))
        ->toThrow(InvalidArgumentException::class, 'Exactly one of comment_id, post_id, or post_url is required.')
        ->and(fn (): CreateCommentData => new CreateCommentData(
            user: 'profile',
            message: 'Thanks',
            platform: Platform::Instagram,
            post_id: 'post_123',
        ))->toThrow(InvalidArgumentException::class, 'comment_id is required.')
        ->and(fn (): CreateCommentData => new CreateCommentData(
            user: 'profile',
            message: 'Thanks',
            platform: Platform::TikTok,
            comment_id: 'comment_123',
        ))->toThrow(InvalidArgumentException::class, 'post_id is required.')
        ->and(fn (): DeleteCommentData => new DeleteCommentData(
            user: 'profile',
            comment_id: 'comment_123',
            platform: Platform::LinkedIn,
        ))->toThrow(InvalidArgumentException::class, 'post_id is required.')
        ->and(fn (): CommentActionData => new CommentActionData(
            user: 'profile',
            action: 'edit',
            platform: Platform::Facebook,
            comment_id: 'comment_123',
        ))->toThrow(InvalidArgumentException::class, 'message is required.')
        ->and(fn (): CommentActionData => new CommentActionData(
            user: 'profile',
            action: 'hide',
            platform: Platform::YouTube,
            comment_id: 'comment_123',
            post_id: 'video_123',
            ban_author: false,
        ))->not->toThrow(InvalidArgumentException::class)
        ->and(fn (): CommentQueryData => new CommentQueryData(
            user: 'profile',
            platform: Platform::Pinterest,
            post_id: 'post_123',
        ))->toThrow(InvalidArgumentException::class, 'pinterest is not supported for listing comments.')
        ->and(fn (): CommentQueryData => new CommentQueryData(
            user: 'profile',
            platform: Platform::TikTok,
            post_url: 'https://tiktok.example/post',
            comment_id: 'parent_123',
        ))->toThrow(InvalidArgumentException::class, 'post_id is required when listing TikTok comment replies.')
        ->and(fn (): CreateCommentData => new CreateCommentData(
            user: 'profile',
            message: 'Thanks',
            platform: Platform::TikTok,
            post_id: 'post_123',
            post_url: 'https://tiktok.example/post',
        ))->toThrow(InvalidArgumentException::class, 'post_url cannot be used for TikTok comments; use post_id.')
        ->and(fn (): CreateCommentData => new CreateCommentData(
            user: 'profile',
            message: 'Thanks',
            platform: Platform::YouTube,
            post_id: 'post_123',
            attachment_url: 'https://example.com/image.jpg',
        ))->toThrow(InvalidArgumentException::class, 'attachment_url and attachment_share_url are only supported for Facebook comments.')
        ->and(fn (): CommentActionData => new CommentActionData(
            user: 'profile',
            action: 'like',
            platform: Platform::Instagram,
            comment_id: 'comment_123',
        ))->toThrow(InvalidArgumentException::class, 'like is not supported for instagram comments.')
        ->and(fn (): CommentActionData => new CommentActionData(
            user: 'profile',
            action: 'hide',
            platform: Platform::Facebook,
            comment_id: 'comment_123',
            ban_author: true,
        ))->toThrow(InvalidArgumentException::class, 'ban_author is only supported for YouTube hide actions.');
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
        'ui_labels' => ['connect.connectButton' => 'Connect'],
    ])->toArray())->toBe([
        'username' => 'profile',
        'platforms' => ['x'],
        'show_calendar' => true,
        'ui_labels' => ['connect.connectButton' => 'Connect'],
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
        'youtube_playlist_id' => 'playlist-123',
        'youtube_subtitles' => [
            ['language' => 'en', 'file' => __FILE__],
        ],
        'idempotency_key' => 'idem-video',
    ]);
    $videoContents = array_column($video->toMultipart()->all(), 'contents', 'name');

    expect($video->idempotency_key)->toBe('idem-video')
        ->and($video->common->async_upload)->toBeTrue()
        ->and($videoContents['youtube_playlist_id'])->toBe('playlist-123')
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
        'visibility' => 'PUBLIC',
    ])->options->visibility)->toBe('PUBLIC');
});

test('request DTOs can be converted to arrays', function (): void {
    $scheduledDate = new DateTimeImmutable('+30 days', new DateTimeZone('UTC'));

    $video = new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::YouTube],
            title: 'Video',
            scheduled_date: $scheduledDate,
            async_upload: false,
        ),
        options: new PlatformOptions(
            tags: ['php'],
            embeddable: false,
            youtube_playlist_id: 'playlist-123',
            youtube_subtitles: [
                new YoutubeSubtitleData(language: 'en', file: __FILE__),
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
            'scheduled_date' => $scheduledDate->format(DateTimeInterface::ATOM),
            'async_upload' => false,
        ],
        'options' => [
            'tags' => ['php'],
            'embeddable' => false,
            'youtube_playlist_id' => 'playlist-123',
            'youtube_subtitles' => [
                ['language' => 'en', 'file' => __FILE__],
            ],
        ],
        'idempotency_key' => 'idem-video',
    ]);

    expect(UploadTextData::fromArray([
        'common' => ['user' => 'profile', 'platforms' => ['x'], 'title' => 'Text'],
        'options' => ['x_long_text_as_post' => true],
    ])->toArray())->toBe([
        'common' => ['user' => 'profile', 'platforms' => ['x'], 'title' => 'Text'],
        'options' => ['x_long_text_as_post' => true],
    ]);

    expect(UploadDocumentData::fromArray([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'first_comment' => 'General first comment',
        'linkedin_first_comment' => 'LinkedIn first comment',
    ])->toArray())->toBe([
        'document' => 'https://example.com/document.pdf',
        'user' => 'profile',
        'title' => 'Document',
        'first_comment' => 'General first comment',
        'linkedin_first_comment' => 'LinkedIn first comment',
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

test('documented platform options round trip through arrays', function (): void {
    expect(PlatformOptions::fromArray([
        'share_mode' => 'TRIAL_REELS_DONT_SHARE_TO_FOLLOWERS',
        'thumbnail' => 'https://example.com/youtube-thumbnail.jpg',
        'reply_to_id' => 'post-123',
        'exclude_reply_user_ids' => ['user-1', 'user-2'],
        'gbp_location_id' => 'locations/123',
        'gbp_topic_type' => 'OFFER',
        'gbp_cta_type' => 'SHOP',
        'gbp_cta_url' => 'https://example.com/shop',
        'gbp_post_type' => 'MEDIA',
        'gbp_upload_to_gallery' => true,
        'gbp_media_category' => 'EXTERIOR',
        'gbp_event_title' => 'Launch',
        'gbp_event_start_date' => '2026-08-01',
        'gbp_event_start_time' => '09:00',
        'gbp_event_end_date' => '2026-08-02',
        'gbp_event_end_time' => '17:00',
        'gbp_coupon_code' => 'SAVE20',
        'gbp_redeem_url' => 'https://example.com/redeem',
        'gbp_terms' => 'Terms apply.',
    ])->toArray())->toBe([
        'share_mode' => 'TRIAL_REELS_DONT_SHARE_TO_FOLLOWERS',
        'thumbnail' => 'https://example.com/youtube-thumbnail.jpg',
        'reply_to_id' => 'post-123',
        'exclude_reply_user_ids' => ['user-1', 'user-2'],
        'gbp_location_id' => 'locations/123',
        'gbp_topic_type' => 'OFFER',
        'gbp_cta_type' => 'SHOP',
        'gbp_cta_url' => 'https://example.com/shop',
        'gbp_post_type' => 'MEDIA',
        'gbp_upload_to_gallery' => true,
        'gbp_media_category' => 'EXTERIOR',
        'gbp_event_title' => 'Launch',
        'gbp_event_start_date' => '2026-08-01',
        'gbp_event_start_time' => '09:00',
        'gbp_event_end_date' => '2026-08-02',
        'gbp_event_end_time' => '17:00',
        'gbp_coupon_code' => 'SAVE20',
        'gbp_redeem_url' => 'https://example.com/redeem',
        'gbp_terms' => 'Terms apply.',
    ])->and(PlatformOptions::fromArray([
        'media_category' => 'MENU',
    ])->toArray())->toBe([
        'gbp_media_category' => 'MENU',
    ])->and(PlatformOptions::fromArray([
        'tiktok_location_name' => 'Lagos',
    ])->tiktok_location_name)->toBe('Lagos');
});

test('Google Business option enums serialize to API values', function (): void {
    expect((new PlatformOptions(
        gbp_topic_type: GoogleBusinessTopicType::Offer,
        gbp_cta_type: GoogleBusinessCtaType::Shop,
        gbp_cta_url: 'https://example.com/shop',
        gbp_post_type: GoogleBusinessPostType::Media,
        gbp_media_category: GoogleBusinessMediaCategory::Exterior,
    ))->toArray())->toMatchArray([
        'gbp_topic_type' => 'OFFER',
        'gbp_cta_type' => 'SHOP',
        'gbp_cta_url' => 'https://example.com/shop',
        'gbp_post_type' => 'MEDIA',
        'gbp_media_category' => 'EXTERIOR',
    ])->and(PlatformOptions::fromArray([
        'gbp_cta_type' => 'shop',
        'gbp_post_type' => 'media',
        'gbp_media_category' => 'exterior',
        'gbp_topic_type' => 'offer',
    ])->toArray())->toMatchArray([
        'gbp_cta_type' => 'SHOP',
        'gbp_post_type' => 'MEDIA',
        'gbp_media_category' => 'EXTERIOR',
        'gbp_topic_type' => 'OFFER',
    ]);
});

test('explicit current platform options round trip without an additional fields bag', function (): void {
    $options = PlatformOptions::fromArray([
        'disable_inbox_fallback' => true,
        'tiktok_cover_image' => __FILE__,
        'tiktok_cover_image_url' => 'https://example.com/cover.jpg',
        'tiktok_is_ads_only' => true,
        'tiktok_location_id' => 'location-123',
        'tiktok_location_name' => 'Lagos',
        'tiktok_music_id' => 'music-123',
        'tiktok_music_start' => '100',
        'tiktok_music_end' => '200',
        'tiktok_music_volume' => '60',
        'tiktok_original_sound_volume' => '40',
        'tiktok_tto_invite_link' => 'https://example.com/invite',
        'instagram_alt_text' => ['first', 'second'],
        'youtube_notify_subscribers' => false,
        'youtube_publish_at' => '2026-12-01T12:00:00Z',
        'linkedin_disable_reshare' => true,
        'linkedin_alt_text' => ['first', 'second'],
        'linkedin_subtitles' => __FILE__,
        'linkedin_subtitles_url' => 'https://example.com/subtitles.srt',
        'linkedin_subtitles_text' => "1\n00:00:00,000 --> 00:00:01,000\nHello",
        'linkedin_link_title' => 'Title',
        'linkedin_link_description' => 'Description',
        'linkedin_thumbnail_alt_text' => 'Thumbnail',
        'linkedin_poll_question' => 'Question?',
        'linkedin_poll_options' => ['Yes', 'No'],
        'linkedin_poll_duration' => '1440',
        'facebook_collaborators' => ['page-1', 'page-2'],
        'facebook_is_ai_generated' => true,
        'facebook_unpublished_content_type' => 'DRAFT',
        'facebook_no_story' => true,
        'facebook_secret' => true,
        'facebook_alt_text' => ['first', 'second'],
        'facebook_place_id' => 'place-123',
        'facebook_targeting' => ['countries' => ['NG']],
        'facebook_feed_targeting' => ['interests' => ['technology']],
        'facebook_call_to_action' => ['type' => 'SHOP_NOW'],
        'facebook_child_attachments' => [['link' => 'https://example.com/one']],
        'facebook_multi_share_end_card' => true,
        'pinterest_board_section_id' => 'section-123',
        'pinterest_ai_disclosures' => ['AI_MODIFIED'],
        'pinterest_carousel_titles' => ['One', 'Two'],
        'pinterest_carousel_descriptions' => ['First', 'Second'],
        'pinterest_carousel_links' => ['https://example.com/one', 'https://example.com/two'],
        'pinterest_carousel_index' => '1',
        'made_with_ai' => true,
        'x_alt_text' => ['first', 'second'],
        'x_subtitles_url' => 'https://example.com/subtitles.srt',
        'x_subtitles_language' => 'en',
        'x_subtitles_name' => 'English',
        'x_paid_partnership' => true,
        'x_article_title' => 'Article',
        'x_article_body' => 'Body',
        'x_article_content_state' => '{"blocks":[]}',
        'x_article_draft' => true,
        'x_article_cover_media' => 123,
        'threads_alt_text' => ['first', 'second'],
        'threads_reply_control' => 'followers_only',
        'threads_reply_to_id' => '123',
        'threads_quote_post_id' => '456',
        'threads_link_attachment' => 'https://example.com',
        'threads_poll_options' => ['Yes', 'No'],
        'threads_auto_publish_text' => true,
        'bluesky_alt_text' => ['first', 'second'],
        'bluesky_langs' => 'en,yo',
        'bluesky_labels' => 'graphic-media',
        'bluesky_gallery' => true,
        'bluesky_threadgate' => 'followers',
        'bluesky_postgate' => 'disable_quotes',
        'bluesky_quote_uri' => 'at://did:plc:example/app.bsky.feed.post/123',
        'discord_alt_text' => 'first|second',
        'discord_thread_id' => 'thread-123',
        'discord_thread_name' => 'Thread',
        'discord_flags' => '4096',
        'discord_max_file_mb' => '50',
        'telegram_parse_mode' => 'HTML',
        'telegram_has_spoiler' => true,
        'telegram_as_document' => true,
        'telegram_disable_notification' => true,
        'telegram_protect_content' => true,
        'gbp_language_code' => 'en-GB',
    ]);

    expect($options->toArray())->toMatchArray([
        'tiktok_music_volume' => 60,
        'linkedin_poll_options' => ['Yes', 'No'],
        'linkedin_subtitles_url' => 'https://example.com/subtitles.srt',
        'linkedin_subtitles_text' => "1\n00:00:00,000 --> 00:00:01,000\nHello",
        'facebook_targeting' => ['countries' => ['NG']],
        'pinterest_carousel_index' => 1,
        'pinterest_ai_disclosures' => ['AI_MODIFIED'],
        'x_article_cover_media' => 123,
        'threads_poll_options' => ['Yes', 'No'],
        'discord_flags' => 4096,
        'gbp_language_code' => 'en-GB',
    ])->not->toHaveKey('additional_fields');
});

test('documented common upload fields and platforms round trip through arrays', function (): void {
    $common = CommonUploadData::fromArray([
        'user' => 'profile',
        'platforms' => [Platform::Discord, Platform::Telegram, Platform::GoogleBusiness],
        'request_id' => 'request-123',
        'discord_title' => 'Discord caption',
        'telegram_title' => 'Telegram caption',
    ]);

    expect($common->toArray())->toBe([
        'user' => 'profile',
        'platforms' => ['discord', 'telegram', 'google_business'],
        'request_id' => 'request-123',
        'discord_title' => 'Discord caption',
        'telegram_title' => 'Telegram caption',
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

    $scheduledDate = (new DateTimeImmutable('+30 days', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

    expect(CommonUploadData::fromArray([
        'user' => 'profile',
        'platforms' => ['x'],
        'scheduled_date' => $scheduledDate,
        'max_posts_per_slot' => '2',
    ])->toArray())->toBe([
        'user' => 'profile',
        'platforms' => ['x'],
        'scheduled_date' => $scheduledDate,
        'max_posts_per_slot' => 2,
    ]);

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

});

test('request DTOs cover date, label, and history error branches', function (): void {
    $scheduled = (new DateTimeImmutable('+1 day', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

    expect((new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        scheduled_date: $scheduled,
        timezone: 'Africa/Lagos',
    ))->timezone)->toBe('Africa/Lagos')
        ->and(fn (): CommonUploadData => new CommonUploadData(
            user: 'profile',
            platforms: [Platform::X],
            scheduled_date: (new DateTimeImmutable('+366 days', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
        ))->toThrow(InvalidArgumentException::class, 'scheduled_date cannot be more than 365 days in the future.')
        ->and(CommonUploadData::fromArray([
            'user' => 'profile',
            'platforms' => ['x'],
            'scheduled_date' => ['invalid'],
        ])->scheduled_date)->toBeNull();

    expect(fn (): GenerateJwtData => new GenerateJwtData(
        username: 'profile',
        ui_labels: array_fill_keys(
            array_map(static fn (int $index): string => "label_{$index}", range(1, 101)),
            'Label',
        ),
    ))->toThrow(InvalidArgumentException::class, 'ui_labels cannot contain more than 100 entries.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(
            username: 'profile',
            ui_labels: [123 => 'Label'],
        ))->toThrow(InvalidArgumentException::class, 'ui_labels keys may only contain letters, numbers, dots, and underscores.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(
            username: 'profile',
            ui_labels: ['title' => 123],
        ))->toThrow(InvalidArgumentException::class, 'ui_labels values must be strings of 300 characters or fewer.')
        ->and(GenerateJwtData::fromArray([
            'username' => 'profile',
            'ui_labels' => 'invalid',
        ])->ui_labels)->toBeNull();

    expect(fn (): HistoryQueryData => new HistoryQueryData(
        start: 'invalid',
        end: 'invalid',
    ))->toThrow(InvalidArgumentException::class, 'start and end must be valid dates.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(
            start: '2026-01-01T25:00:00Z',
            end: '2026-01-02T00:00:00Z',
        ))->toThrow(InvalidArgumentException::class, 'start and end must be valid dates.')
        ->and(fn (): HistoryQueryData => new HistoryQueryData(
            start: '2026-02-01',
            end: '2026-01-01',
        ))->toThrow(InvalidArgumentException::class, 'start must be before or equal to end.');
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

test('generate jwt data validates required profile and language values', function (): void {
    expect(fn (): GenerateJwtData => new GenerateJwtData(username: ' '))
        ->toThrow(InvalidArgumentException::class, 'username is required.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(username: 'profile', language: 'nl'))
        ->toThrow(InvalidArgumentException::class, 'language must be one of: en, es, de, fr, pt, pl, tr.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(username: 'profile', redirect_url: 'ftp://example.com/callback'))
        ->toThrow(InvalidArgumentException::class, 'redirect_url must be an absolute HTTP(S) URL.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(username: 'profile', redirect_url: 'not-a-url'))
        ->toThrow(InvalidArgumentException::class, 'redirect_url must be an absolute HTTP(S) URL.')
        ->and(fn (): GenerateJwtData => new GenerateJwtData(username: 'profile', redirect_url: str_repeat('a', 2001)))
        ->toThrow(InvalidArgumentException::class, 'redirect_url must be 2000 characters or fewer.');
});

test('generate jwt data serializes language enums', function (): void {
    expect((new GenerateJwtData(username: 'profile', language: JwtLanguage::Turkish))->toArray())
        ->toHaveKey('language', 'tr');
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
        'message' => 'Published successfully',
        'results' => [[
            'platform' => 'tiktok',
            'upload_timestamp' => '2026-01-01T00:00:00Z',
            'skipped' => 'true',
            'skip_reason' => 'profile_platform_not_configured',
            'failure_stage' => 'profile_platform_validation',
            'thumbnail_error' => 'LinkedIn rejected the thumbnail.',
            'post_url' => 'Video sent to Inbox (No Public URL)',
            'restriction_reason' => 'daily_upload_limit',
            'restricted_until' => '2026-01-01T02:00:00Z',
            'retry_after_seconds' => '3600',
            'fallback_to_inbox' => 'true',
        ]],
        'last_update' => '2026-01-01T00:00:00Z',
    ]);
    $upload = UploadResponse::fromArray(['success' => true, 'request_id' => 123, 'external_id' => 'post-123', 'job_id' => 456, 'status' => 'queued', 'message' => 'ok', 'warnings' => ['top-level warning'], 'results' => ['linkedin' => ['id' => 1, 'article_id' => 2, 'draft' => false, 'photos_were_processed' => true, 'video_was_transcoded' => true, 'video_urn' => 'urn:li:video:1', 'document_urn' => 'urn:li:document:1', 'content_type' => 'application/pdf', 'file_size' => '42', 'filename' => 'deck.pdf', 'error_source' => 'linkedin', 'linkedin_status' => 502, 'retryable' => true, 'changes' => ['caption' => true], 'changes_per_image' => [['resized' => true]], 'prevalidation_metadata' => ['duration' => 10], 'first_comment_posted' => true, 'warnings' => ['notice']]], 'total_platforms' => '2', 'scheduled_date' => '2026-01-01T12:00:00Z', 'usage' => ['count' => '2', 'limit' => 100, 'last_reset' => '2026-01-01T00:00:00Z']]);
    $impressions = TotalImpressionsResponse::fromArray([
        'success' => true,
        'profile_username' => 'profile',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-07',
        'total_impressions' => '42',
        'metrics' => ['impressions' => 42],
        'per_platform' => ['instagram' => 42],
        'per_day' => ['2026-01-01' => 42],
        'platforms_filter' => ['instagram', 123],
    ]);
    $action = ActionResponse::fromArray([
        'success' => true,
        'message' => 'Job cancelled.',
        'credits_refunded' => '2',
    ]);
    $postAnalytics = PostAnalyticsResponse::fromArray([
        'success' => true,
        'post' => [
            'request_id' => 'request-123',
            'platform_post_id' => 'video-123',
            'platform' => 'youtube',
            'profile_username' => 'profile',
            'source' => 'api_uploaded',
            'post_title' => 'Post title',
            'post_caption' => 'Caption',
            'media_type' => 'video',
            'upload_timestamp' => '2026-02-10 14:30:00',
        ],
        'platforms' => [
            'youtube' => [
                'success' => true,
                'platform_post_id' => 'video-123',
                'post_url' => 'https://youtube.com/watch?v=video-123',
                'post_metrics' => ['views' => 42],
                'post_metrics_source' => 'platform_api',
                'profile_snapshot_at_post_date' => ['followers' => 10],
                'profile_snapshot_latest' => ['followers' => 12],
                'profile_snapshot_latest_date' => '2026-02-20',
                'available_metrics' => ['views', 123],
                'metric_labels' => ['views' => 'Video Views', 'invalid' => 123],
                'primary_impressions_field' => 'views',
            ],
        ],
    ]);
    $platformMetrics = PlatformMetricsResponse::fromArray([
        'youtube' => [
            'primary_impressions_field' => 'impressions',
            'available_metrics' => ['followers', 'views', 123],
            'metric_labels' => ['views' => 'Video Views', 'invalid' => 123],
        ],
    ]);
    $user = UserResponse::fromArray(['success' => true, 'profile' => ['username' => 'profile']]);
    $history = HistoryResponse::fromArray(['history' => [[
        'id' => 1,
        'external_id' => 'cms-post-123',
        'fallback_to_inbox' => 'true',
        'changes' => ['caption' => true],
        'prevalidation_metadata' => ['duration' => 10],
        'dashboard' => ['source' => 'dashboard'],
    ], ], 'in_progress' => [['id' => 2]], 'total' => '1', 'page' => '2', 'limit' => '50']);
    $scheduled = ScheduledPostsResponse::fromArray([
        'scheduled_posts' => [[
            'job_id' => 'job',
            'source_filename' => 'video.mp4',
            'caption' => 'Caption',
            'description' => 'Description',
            'platform_content' => ['tiktok' => ['title' => 'Title']],
            'fields' => ['privacy_level' => 'PUBLIC_TO_EVERYONE'],
            'has_cover' => 'true',
            'cover_preview_url' => 'https://example.com/cover.jpg',
            'has_preview' => true,
            'preview_url' => null,
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'original_timezone' => 'Africa/Lagos',
            'original_scheduled_str' => '2026-01-01 12:00:00',
        ]],
        'total' => '42',
        'limit' => '20',
        'offset' => '0',
    ]);
    $scheduledRootList = ScheduledPostsResponse::fromArray([[
        'job_id' => 'job-from-root-list',
        'scheduled_date' => '2026-01-01T12:00:00Z',
        'post_type' => 'video',
        'profile_username' => 'profile',
        'title' => 'Scheduled post',
        'external_id' => 'cms-post-123',
    ]]);
    $scheduledPost = ScheduledPostResponse::fromArray(['success' => true, 'job_id' => 'job', 'scheduled_date' => '2026-01-01T00:00:00Z', 'title' => 'Title', 'caption' => 'Caption']);
    $resources = ResourceListResponse::fromArray(['success' => true, 'boards' => [['id' => 'board']], 'pinterest_account_used' => 'pin'], 'boards');
    $facebookPages = FacebookPagesResponse::fromArray(['success' => true, 'pages' => [['id' => 'facebook-page', 'followers' => '42', 'likes' => 40]]]);
    $facebookPagesCurrent = FacebookPagesResponse::fromArray(['success' => true, 'pages' => [[
        'page_id' => 'facebook-page-current',
        'page_name' => 'Current Page Name',
        'profile' => 'profile',
    ]]]);
    $linkedinPages = LinkedinPagesResponse::fromArray(['success' => true, 'pages' => [['id' => 'linkedin-page', 'vanityName' => 'company', 'followers' => 12]]]);
    $pinterestBoards = PinterestBoardsResponse::fromArray(['success' => true, 'boards' => [['id' => 'board']], 'pinterest_account_used' => 'pin']);
    $googleBusinessLocations = GoogleBusinessLocationsResponse::fromArray([
        'success' => true,
        'locations' => [['name' => 'accounts/1/locations/2']],
        'selected_location_id' => 'locations/2',
        'selected_location_name' => 'Main Street Store',
    ]);
    $queueSettings = QueueSettingsResponse::fromArray([
        'success' => true,
        'queue_settings' => [
            'timezone' => 'America/New_York',
            'slots' => [['hour' => 9, 'minute' => 0]],
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'max_posts_per_slot' => 1,
            'full_slots' => [],
        ],
    ]);
    $queueSettingsWithoutArray = QueueSettingsResponse::fromArray([
        'success' => true,
        'queue_settings' => 'invalid',
    ]);
    $queueSettingsWithNumericKeys = QueueSettingsResponse::fromArray([
        'success' => true,
        'queue_settings' => [
            ['hour' => 9, 'minute' => 0],
            'timezone' => 'America/New_York',
        ],
    ]);
    $queuePreview = QueuePreviewResponse::fromArray([
        'success' => true,
        'timezone' => 'America/New_York',
        'max_posts_per_slot' => '3',
        'slots' => [[
            'datetime_utc' => '2026-01-01T14:00:00+00:00',
            'scheduled_post' => [
                'job_id' => 'legacy-job',
                'title' => 'Legacy scheduled post',
                'platforms' => ['instagram'],
            ],
        ]],
        'next_available' => '2026-01-01T14:00:00+00:00',
    ]);
    $queueSlotFull = QueueSlotFullResponse::fromArray([
        'success' => true,
        'message' => 'Slot marked as full',
        'full_slots' => ['2026-01-01T14:00:00+00:00'],
    ]);
    $queueNextSlot = QueueNextSlotResponse::fromArray([
        'success' => true,
        'next_slot' => [
            'datetime_utc' => '2026-01-01T14:00:00+00:00',
            'datetime_local' => '2026-01-01T09:00:00-05:00',
            'timezone' => 'America/New_York',
        ],
    ]);
    $queueNextSlotEmpty = QueueNextSlotResponse::fromArray([
        'success' => true,
        'next_slot' => null,
        'message' => 'No available slots found',
    ]);
    $users = UserProfilesResponse::fromArray(['success' => true, 'profiles' => [[
        'username' => 'profile',
        'ui_labels' => ['connect.connectButton' => 'Connect'],
        'selected_location_id' => 'locations/1',
        'selected_location_name' => 'Main Street Store',
        'facebook_page_id' => 'fb-page',
        'facebook_page_name' => 'Facebook Page',
        'linkedin_page_id' => 'li-page',
        'linkedin_page_name' => 'LinkedIn Page',
        'selected_page_id' => 'selected-page',
        'selected_page_name' => 'Selected Page',
    ]], 'limit' => '5', 'plan' => 'pro']);
    $notifications = NotificationConfigResponse::fromArray([
        'success' => true,
        'notifications' => ['webhook_url' => 'https://example.com/webhook'],
    ]);
    $preferences = UserPreferencesResponse::fromArray([
        'success' => true,
        'preferences' => ['weekStartDay' => 1],
    ]);

    expect($generic->get('nested.value'))->toBe('ok')
        ->and($generic->toArray())->toBe(['nested' => ['value' => 'ok']])
        ->and($jwt->jwt)->toBe('jwt-token')
        ->and($jwt->url)->toBe('https://connect.example.com')
        ->and($jwtFromApi->success)->toBeTrue()
        ->and($jwtFromApi->url)->toBe('https://connect.example.com/new')
        ->and($jwtFromApi->duration)->toBe('36h')
        ->and($listFromData->items[0]->id)->toBe('1')
        ->and($listFromItems->items[0]->id)->toBe('2')
        ->and($listFromRawList->items[0]->id)->toBe('3')
        ->and(ListResponse::fromArray(['data' => 'invalid'])->items)->toBe([])
        ->and(CommentsResponse::fromArray(['comments' => 'invalid'])->comments)->toBe([])
        ->and($status->status)->toBe('done')
        ->and($status->request_id)->toBe('123')
        ->and($status->job_id)->toBe('job')
        ->and($status->completed)->toBe(1)
        ->and($status->total)->toBe(2)
        ->and($status->message)->toBe('Published successfully')
        ->and($status->results[0]->platform)->toBe('tiktok')
        ->and($status->results[0]->upload_timestamp)->toBe('2026-01-01T00:00:00Z')
        ->and($status->results[0]->skipped)->toBeTrue()
        ->and($status->results[0]->skip_reason)->toBe('profile_platform_not_configured')
        ->and($status->results[0]->failure_stage)->toBe('profile_platform_validation')
        ->and($status->results[0]->thumbnail_error)->toBe('LinkedIn rejected the thumbnail.')
        ->and($status->results[0]->url)->toBe('Video sent to Inbox (No Public URL)')
        ->and($status->results[0]->post_url)->toBe('Video sent to Inbox (No Public URL)')
        ->and($status->results[0]->restriction_reason)->toBe('daily_upload_limit')
        ->and($status->results[0]->restricted_until)->toBe('2026-01-01T02:00:00Z')
        ->and($status->results[0]->retry_after_seconds)->toBe(3600)
        ->and($status->results[0]->fallback_to_inbox)->toBeTrue()
        ->and($action->credits_refunded)->toBe(2)
        ->and($postAnalytics->success)->toBeTrue()
        ->and($postAnalytics->post?->request_id)->toBe('request-123')
        ->and($postAnalytics->post?->source)->toBe('api_uploaded')
        ->and($postAnalytics->platforms['youtube']->post_url)->toBe('https://youtube.com/watch?v=video-123')
        ->and($postAnalytics->platforms['youtube']->post_metrics['views'])->toBe(42)
        ->and($postAnalytics->platforms['youtube']->available_metrics)->toBe(['views'])
        ->and($postAnalytics->platforms['youtube']->metric_labels)->toBe(['views' => 'Video Views'])
        ->and($postAnalytics->platforms['youtube']->primary_impressions_field)->toBe('views')
        ->and($platformMetrics->platforms['youtube']->primary_impressions_field)->toBe('impressions')
        ->and($platformMetrics->platforms['youtube']->available_metrics)->toBe(['followers', 'views'])
        ->and($platformMetrics->platforms['youtube']->metric_labels)->toBe(['views' => 'Video Views'])
        ->and($status->last_update)->toBe('2026-01-01T00:00:00Z')
        ->and($upload->request_id)->toBe('123')
        ->and($upload->external_id)->toBe('post-123')
        ->and($upload->job_id)->toBe('456')
        ->and($upload->status)->toBe('queued')
        ->and($upload->message)->toBe('ok')
        ->and($upload->success)->toBeTrue()
        ->and($upload->warnings)->toBe(['top-level warning'])
        ->and($upload->total_platforms)->toBe(2)
        ->and($upload->scheduled_date)->toBe('2026-01-01T12:00:00Z')
        ->and($upload->usage?->count)->toBe(2)
        ->and($upload->usage?->limit)->toBe(100)
        ->and($upload->results[0]->platform)->toBe('linkedin')
        ->and($upload->results[0]->post_id)->toBe('1')
        ->and($upload->results[0]->article_id)->toBe('2')
        ->and($upload->results[0]->draft)->toBeFalse()
        ->and($upload->results[0]->photos_were_processed)->toBeTrue()
        ->and($upload->results[0]->video_was_transcoded)->toBeTrue()
        ->and($upload->results[0]->video_urn)->toBe('urn:li:video:1')
        ->and($upload->results[0]->document_urn)->toBe('urn:li:document:1')
        ->and($upload->results[0]->content_type)->toBe('application/pdf')
        ->and($upload->results[0]->file_size)->toBe(42)
        ->and($upload->results[0]->filename)->toBe('deck.pdf')
        ->and($upload->results[0]->error_source)->toBe('linkedin')
        ->and($upload->results[0]->linkedin_status)->toBe(502)
        ->and($upload->results[0]->retryable)->toBeTrue()
        ->and($upload->results[0]->changes)->toBe(['caption' => true])
        ->and($upload->results[0]->changes_per_image)->toBe([['resized' => true]])
        ->and($upload->results[0]->prevalidation_metadata)->toBe(['duration' => 10])
        ->and($upload->results[0]->first_comment_posted)->toBeTrue()
        ->and($upload->results[0]->warnings)->toBe(['notice'])
        ->and($impressions->total_impressions)->toBe(42)
        ->and($impressions->metrics)->toBe(['impressions' => 42])
        ->and($impressions->platforms_filter)->toBe(['instagram'])
        ->and($user->username)->toBe('profile')
        ->and($user->success)->toBeTrue()
        ->and($user->profile?->username)->toBe('profile')
        ->and($history->history[0]->external_id)->toBe('cms-post-123')
        ->and($history->history[0]->fallback_to_inbox)->toBeTrue()
        ->and($history->history[0]->changes)->toBe(['caption' => true])
        ->and($history->history[0]->prevalidation_metadata)->toBe(['duration' => 10])
        ->and($history->history[0]->dashboard)->toBe(['source' => 'dashboard'])
        ->and($history->history[0]->raw)->toBe([
            'id' => 1,
            'external_id' => 'cms-post-123',
            'fallback_to_inbox' => 'true',
            'changes' => ['caption' => true],
            'prevalidation_metadata' => ['duration' => 10],
            'dashboard' => ['source' => 'dashboard'],
        ])
        ->and($history->total)->toBe(1)
        ->and($history->in_progress[0]->raw)->toBe(['id' => 2])
        ->and($scheduled->scheduled_posts[0]->job_id)->toBe('job')
        ->and($scheduled->total)->toBe(42)
        ->and($scheduled->limit)->toBe(20)
        ->and($scheduled->offset)->toBe(0)
        ->and($scheduled->scheduled_posts[0]->source_filename)->toBe('video.mp4')
        ->and($scheduled->scheduled_posts[0]->caption)->toBe('Caption')
        ->and($scheduled->scheduled_posts[0]->description)->toBe('Description')
        ->and($scheduled->scheduled_posts[0]->platform_content)->toBe(['tiktok' => ['title' => 'Title']])
        ->and($scheduled->scheduled_posts[0]->fields)->toBe(['privacy_level' => 'PUBLIC_TO_EVERYONE'])
        ->and($scheduled->scheduled_posts[0]->has_cover)->toBeTrue()
        ->and($scheduled->scheduled_posts[0]->cover_preview_url)->toBe('https://example.com/cover.jpg')
        ->and($scheduled->scheduled_posts[0]->has_preview)->toBeTrue()
        ->and($scheduled->scheduled_posts[0]->thumbnail_url)->toBe('https://example.com/thumb.jpg')
        ->and($scheduled->scheduled_posts[0]->original_timezone)->toBe('Africa/Lagos')
        ->and($scheduled->scheduled_posts[0]->original_scheduled_str)->toBe('2026-01-01 12:00:00')
        ->and($scheduledRootList->scheduled_posts[0]->job_id)->toBe('job-from-root-list')
        ->and($scheduledRootList->scheduled_posts[0]->profile_username)->toBe('profile')
        ->and($scheduledRootList->scheduled_posts[0]->external_id)->toBe('cms-post-123')
        ->and($scheduledPost->job_id)->toBe('job')
        ->and($scheduledPost->caption)->toBe('Caption')
        ->and($resources->items[0]->id)->toBe('board')
        ->and($resources->pinterest_account_used)->toBe('pin')
        ->and($facebookPages->pages[0]->id)->toBe('facebook-page')
        ->and($facebookPages->pages[0]->followers)->toBe(42)
        ->and($facebookPages->pages[0]->likes)->toBe(40)
        ->and($facebookPagesCurrent->pages[0]->id)->toBe('facebook-page-current')
        ->and($facebookPagesCurrent->pages[0]->name)->toBe('Current Page Name')
        ->and($facebookPagesCurrent->pages[0]->profile)->toBe('profile')
        ->and($linkedinPages->pages[0]->vanity_name)->toBe('company')
        ->and($linkedinPages->pages[0]->followers)->toBe(12)
        ->and($pinterestBoards->boards[0]->id)->toBe('board')
        ->and($pinterestBoards->pinterest_account_used)->toBe('pin')
        ->and($googleBusinessLocations->locations[0]->id)->toBe('accounts/1/locations/2')
        ->and($googleBusinessLocations->selected_location_id)->toBe('locations/2')
        ->and($googleBusinessLocations->selected_location_name)->toBe('Main Street Store')
        ->and($queueSettings->queue_settings?->timezone)->toBe('America/New_York')
        ->and($queueSettings->queue_settings?->slots[0]->hour)->toBe(9)
        ->and($queueSettingsWithoutArray->queue_settings)->toBeNull()
        ->and($queueSettingsWithNumericKeys->queue_settings?->timezone)->toBe('America/New_York')
        ->and($queuePreview->timezone)->toBe('America/New_York')
        ->and($queuePreview->max_posts_per_slot)->toBe(3)
        ->and($queuePreview->slots[0]->datetime_utc)->toBe('2026-01-01T14:00:00+00:00')
        ->and($queuePreview->slots[0]->scheduled_post?->job_id)->toBe('legacy-job')
        ->and($queuePreview->slots[0]->scheduled_post?->platforms)->toBe(['instagram'])
        ->and($queuePreview->next_available)->toBe('2026-01-01T14:00:00+00:00')
        ->and($queueSlotFull->message)->toBe('Slot marked as full')
        ->and($queueSlotFull->full_slots)->toBe(['2026-01-01T14:00:00+00:00'])
        ->and($queueNextSlot->next_slot?->timezone)->toBe('America/New_York')
        ->and($queueNextSlotEmpty->next_slot)->toBeNull()
        ->and($queueNextSlotEmpty->message)->toBe('No available slots found')
        ->and($users->profiles[0]->username)->toBe('profile')
        ->and($users->profiles[0]->ui_labels)->toBe(['connect.connectButton' => 'Connect'])
        ->and($users->profiles[0]->selected_location_id)->toBe('locations/1')
        ->and($users->profiles[0]->facebook_page_id)->toBe('fb-page')
        ->and($users->profiles[0]->linkedin_page_name)->toBe('LinkedIn Page')
        ->and($users->profiles[0]->selected_page_id)->toBe('selected-page')
        ->and($users->profiles[0]->selected_page_name)->toBe('Selected Page')
        ->and($users->limit)->toBe(5)
        ->and($notifications->success)->toBeTrue()
        ->and($notifications->notifications?->webhook_url)->toBe('https://example.com/webhook')
        ->and($preferences->success)->toBeTrue()
        ->and($preferences->preferences?->week_start_day)->toBe(1);
});

test('response collections expose endpoint-specific DTO fields and preserve unknown fields', function (): void {
    $comments = CommentsResponse::fromArray(['partial' => true, 'comments' => [[
        'id' => 'comment-1',
        'text' => 'Great post!',
        'timestamp' => '2026-01-01T12:00:00+00:00',
        'user' => ['id' => 'user-1', 'username' => 'commenter'],
        'provider_metadata' => ['future_field' => true],
    ]], 'pagination' => ['next_cursor' => 'cursor-1', 'has_next' => true]]);
    $media = MediaResponse::fromArray(['media' => [[
        'id' => 'media-1',
        'caption' => 'A caption',
        'media_type' => 'IMAGE',
        'media_url' => 'https://example.com/image.jpg',
        'permalink' => 'https://example.com/post',
        'timestamp' => '2026-01-01T12:00:00+00:00',
        'thumbnail_url' => 'https://example.com/thumb.jpg',
    ]], 'pagination' => ['next_cursor' => 'cursor-2', 'has_more' => true, 'limit' => 50]]);
    $history = HistoryResponse::fromArray(['history' => [[
        'profile_username' => 'profile',
        'platform' => 'instagram',
        'media_type' => 'photo',
        'upload_timestamp' => '2026-01-01T12:00:00+00:00',
        'success' => true,
        'platform_post_id' => 'post-1',
        'post_url' => 'https://example.com/post',
        'request_id' => 'req-1',
        'dashboard' => true,
    ]]]);
    $profiles = UserProfilesResponse::fromArray(['profiles' => [[
        'username' => 'profile',
        'social_accounts' => [
            'instagram' => ['display_name' => 'Instagram profile'],
            'invalid-numeric-key',
        ],
    ], [
        'username' => 'profile-with-invalid-accounts',
        'social_accounts' => 'invalid',
    ]]]);

    expect($comments->comments[0]->username)->toBe('commenter')
        ->and($comments->comments[0]->user_id)->toBe('user-1')
        ->and($comments->comments[0]->raw['provider_metadata'])->toBe(['future_field' => true])
        ->and($comments->pagination?->has_next)->toBeTrue()
        ->and($comments->partial)->toBeTrue()
        ->and($media->media[0]->media_type)->toBe('IMAGE')
        ->and($media->media[0]->permalink)->toBe('https://example.com/post')
        ->and($media->pagination?->limit)->toBe(50)
        ->and($history->history[0]->platform_post_id)->toBe('post-1')
        ->and($history->history[0]->request_id)->toBe('req-1')
        ->and($history->history[0]->dashboard)->toBeTrue()
        ->and($profiles->profiles[0]->social_accounts)->toBe([
            'instagram' => ['display_name' => 'Instagram profile'],
        ])
        ->and($profiles->profiles[1]->social_accounts)->toBe([]);
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
        'id' => 456,
        'platform' => 'tiktok',
        'action' => 'hide',
        'comment_id' => 789,
        'gbp_location_id' => 123,
        'gbp_location_name' => 'Main Street Store',
        'result' => ['comment_id' => '789'],
    ]);

    expect($reply->success)->toBeTrue()
        ->and($reply->recipient_id)->toBe('123')
        ->and($reply->message_id)->toBe('mid')
        ->and($reply->id)->toBe('456')
        ->and($reply->platform)->toBe('tiktok')
        ->and($reply->action)->toBe('hide')
        ->and($reply->comment_id)->toBe('789')
        ->and($reply->gbp_location_id)->toBe('123')
        ->and($reply->gbp_location_name)->toBe('Main Street Store')
        ->and($reply->result)->toBe(['comment_id' => '789'])
        ->and(CommentsResponse::fromArray(['comments' => []])->get('missing'))->toBeNull()
        ->and(FacebookPagesResponse::fromArray(['pages' => []])->missing)->toBeNull()
        ->and(GoogleBusinessLocationsResponse::fromArray(['locations' => []])->missing)->toBeNull()
        ->and(HistoryResponse::fromArray(['history' => []])->get('missing'))->toBeNull()
        ->and(LinkedinPagesResponse::fromArray(['pages' => []])->missing)->toBeNull()
        ->and(MediaResponse::fromArray(['media' => []])->get('missing'))->toBeNull()
        ->and(PinterestBoardsResponse::fromArray(['boards' => []])->missing)->toBeNull()
        ->and(QueuePreviewResponse::fromArray(['slots' => []])->get('missing'))->toBeNull()
        ->and(ScheduledPostsResponse::fromArray(['scheduled_posts' => []])->get('missing'))->toBeNull()
        ->and(UserProfilesResponse::fromArray(['profiles' => []])->get('missing'))->toBeNull();
});

test('youtube subtitle data adds media subtitle fields', function (): void {
    $payload = new MultipartPayload;

    (new YoutubeSubtitleData(language: 'en', file: __FILE__))->addTo($payload, 1);

    $parts = $payload->all();

    expect($parts[0])->toBe(['name' => 'youtube_subtitle_language_1', 'contents' => 'en'])
        ->and($parts[1]['name'])->toBe('youtube_subtitle_file_1')
        ->and($parts[1]['filename'])->toBe(basename(__FILE__))
        ->and(is_resource($parts[1]['contents']))->toBeTrue();
});

test('common upload data validates required fields, unavailable platforms, and dates', function (): void {
    expect(fn (): CommonUploadData => new CommonUploadData(user: '', platforms: [Platform::X]))
        ->toThrow(InvalidArgumentException::class, 'user is required.')
        ->and(fn (): CommonUploadData => new CommonUploadData(user: 'profile', platforms: []))
        ->toThrow(InvalidArgumentException::class, 'At least one platform is required.')
        ->and(fn (): CommonUploadData => new CommonUploadData(user: 'profile', platforms: ['']))
        ->toThrow(InvalidArgumentException::class, 'Platform values cannot be blank.')
        ->and(fn (): CommonUploadData => new CommonUploadData(user: 'profile', platforms: [Platform::Reddit]))
        ->toThrow(InvalidArgumentException::class, 'Reddit uploads are currently unavailable.')
        ->and(fn (): CommonUploadData => new CommonUploadData(
            user: 'profile',
            platforms: [Platform::X],
            first_comment_media: [new SplFileInfo(__FILE__)],
        ))->toThrow(InvalidArgumentException::class, 'first_comment_media is unavailable while Reddit uploads are unavailable.');

    expect(fn (): CommonUploadData => new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        scheduled_date: '2020-01-01T00:00:00Z',
    ))->toThrow(InvalidArgumentException::class, 'scheduled_date must be in the future.')
        ->and(fn (): CommonUploadData => new CommonUploadData(
            user: 'profile',
            platforms: [Platform::X],
            scheduled_date: 'invalid-date',
        ))->toThrow(InvalidArgumentException::class, 'scheduled_date must be a valid ISO 8601 date.')
        ->and(fn (): UploadDocumentData => new UploadDocumentData(
            document: __FILE__,
            user: 'profile',
            title: 'Document',
            scheduled_date: '2020-01-01T00:00:00Z',
        ))->toThrow(InvalidArgumentException::class, 'scheduled_date must be in the future.');

    $scheduledDate = new DateTimeImmutable('+30 days', new DateTimeZone('UTC'));
    $scheduledPayload = new MultipartPayload;
    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        title: 'Post',
        scheduled_date: $scheduledDate,
    ))->addForTextTo($scheduledPayload);

    $scheduledContents = array_column($scheduledPayload->all(), 'contents', 'name');

    expect($scheduledContents['scheduled_date'])->toBe($scheduledDate->format(DateTimeInterface::ATOM));
});

test('upload DTOs reject invalid media inputs during construction', function (): void {
    expect(fn (): UploadVideoData => new UploadVideoData(
        video: '',
        common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok]),
    ))->toThrow(InvalidArgumentException::class, 'Invalid media for video.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: [new stdClass],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram]),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media for photos[].')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: [123],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram]),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media for photos[].')
        ->and(fn (): UploadDocumentData => new UploadDocumentData(
            document: new stdClass,
            user: 'profile',
            title: 'Document',
        ))->toThrow(InvalidArgumentException::class, 'Invalid media for document.');
});

test('document uploads include LinkedIn options in multipart payloads', function (): void {
    $payload = (new UploadDocumentData(
        document: 'https://example.com/document.pdf',
        user: 'profile',
        title: 'Document',
        options: new PlatformOptions(visibility: 'PUBLIC'),
    ))->toMultipart();

    expect(array_column($payload->all(), 'contents', 'name')['visibility'])->toBe('PUBLIC');
});

test('platform strings are normalized before serialization', function (): void {
    $data = new CommonUploadData(user: 'profile', platforms: [' instagram ', Platform::TikTok]);

    expect($data->toArray()['platforms'])->toBe(['instagram', 'tiktok']);
});

test('upload data validates documented platform requirements', function (): void {
    expect(fn (): UploadTextData => new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X]),
    ))->toThrow(InvalidArgumentException::class, 'title is required for text uploads.')
        ->and((new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X]),
            options: new PlatformOptions(x_article_title: 'An article headline'),
        ))->toArray())->toMatchArray([
            'common' => ['user' => 'profile', 'platforms' => ['x']],
            'options' => ['x_article_title' => 'An article headline'],
        ])
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::YouTube]),
        ))->toThrow(InvalidArgumentException::class, 'title is required for YouTube video uploads.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Pinterest], title: 'Post'),
        ))->toThrow(InvalidArgumentException::class, 'pinterest_board_id is required for Pinterest uploads.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Pinterest], title: 'Post'),
        ))->toThrow(InvalidArgumentException::class, 'pinterest_board_id is required for Pinterest uploads.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::YouTube], title: 'Post'),
        ))->toThrow(InvalidArgumentException::class, 'YouTube is not supported for photo uploads.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok], title: 'Post'),
        ))->toThrow(InvalidArgumentException::class, 'tiktok is not supported for text uploads.');
});

test('upload data validates documented conditional constraints', function (): void {
    $scheduledDate = (new DateTimeImmutable('+30 days', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

    expect(fn (): CommonUploadData => new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        scheduled_date: $scheduledDate,
        add_to_queue: true,
    ))->toThrow(InvalidArgumentException::class, 'scheduled_date cannot be used with add_to_queue.')
        ->and(fn (): CommonUploadData => new CommonUploadData(
            user: 'profile',
            platforms: [Platform::X],
            scheduled_date: $scheduledDate,
            first_comment_media: [__FILE__],
        ))->toThrow(InvalidArgumentException::class, 'first_comment_media cannot be used with scheduled or queued uploads.')
        ->and(fn (): YoutubeSubtitleData => new YoutubeSubtitleData(
            language: '',
            file: __FILE__,
        ))->toThrow(InvalidArgumentException::class, 'language is required for YouTube subtitles.')
        ->and(fn (): YoutubeSubtitleData => new YoutubeSubtitleData(
            language: 'en',
            file: '',
        ))->toThrow(InvalidArgumentException::class, 'file is required for YouTube subtitles.')
        ->and(fn (): YoutubeSubtitleData => new YoutubeSubtitleData(
            language: 'en',
            file: 'https://example.com/subtitles.vtt',
        ))->toThrow(InvalidArgumentException::class, 'YouTube subtitle files must be uploaded files or local file paths.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(quote_tweet_id: 'tweet-123'),
        ))->toThrow(InvalidArgumentException::class, 'quote_tweet_id cannot be used with X video uploads.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(quote_tweet_id: 'tweet-123'),
        ))->toThrow(InvalidArgumentException::class, 'quote_tweet_id cannot be used with X photo uploads.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::YouTube], title: 'Post'),
            options: new PlatformOptions(allowedCountries: 'US', blockedCountries: 'CA'),
        ))->toThrow(InvalidArgumentException::class, 'allowedCountries and blockedCountries cannot be used together.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram], title: 'Post'),
            options: new PlatformOptions(media_type: 'IMAGE'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media type for Instagram video uploads.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram], title: 'Post'),
            options: new PlatformOptions(cover_image: 'https://example.com/cover.jpg'),
        ))->toThrow(InvalidArgumentException::class, 'URLs must use cover_url instead of cover_image for Instagram video uploads.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok], title: 'Post'),
            options: new PlatformOptions(tiktok_cover_image: 'https://example.com/cover.jpg'),
        ))->toThrow(InvalidArgumentException::class, 'URLs must use tiktok_cover_image_url instead of tiktok_cover_image.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(linkedin_subtitles: 'https://example.com/subtitles.srt'),
        ))->toThrow(InvalidArgumentException::class, 'URLs must use linkedin_subtitles_url instead of linkedin_subtitles.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram], title: 'Post'),
            options: new PlatformOptions(media_type: 'REELS'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media type for Instagram photo uploads.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Facebook], title: 'Post'),
            options: new PlatformOptions(facebook_media_type: 'POSTS'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media type for Facebook video uploads.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Facebook], title: 'Post'),
            options: new PlatformOptions(facebook_media_type: 'REELS'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid media type for Facebook photo uploads.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(visibility: 'CONNECTIONS'),
        ))->not->toThrow(InvalidArgumentException::class)
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(visibility: 'INVALID'),
        ))->toThrow(
            InvalidArgumentException::class,
            'visibility must be one of PUBLIC, CONNECTIONS, LOGGED_IN, or CONTAINER for LinkedIn photo uploads.'
        )
        ->and(fn (): UploadDocumentData => new UploadDocumentData(
            document: 'https://example.com/document.pdf',
            user: 'profile',
            title: 'Document',
            options: new PlatformOptions(visibility: 'INVALID'),
        ))->toThrow(
            InvalidArgumentException::class,
            'visibility must be one of PUBLIC, CONNECTIONS, LOGGED_IN, or CONTAINER for LinkedIn document uploads.'
        )
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Pinterest], title: 'Post'),
            options: new PlatformOptions(
                pinterest_board_id: 'board-123',
                pinterest_cover_image_data: 'encoded',
            ),
        ))->toThrow(InvalidArgumentException::class, 'pinterest_cover_image_data and pinterest_cover_image_content_type must be used together.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(quote_tweet_id: 'tweet-123', card_uri: 'card'),
        ))->toThrow(InvalidArgumentException::class, 'quote_tweet_id, card_uri, direct_message_deep_link, and poll_options are mutually exclusive for X text uploads.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(poll_options: ['yes', 'no'], x_article_title: 'Article'),
        ))->toThrow(InvalidArgumentException::class, 'x_article_title cannot be combined with poll_options, quote_tweet_id, reply_to_id, card_uri, or direct_message_deep_link.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(x_article_body: 'Body'),
        ))->toThrow(InvalidArgumentException::class, 'x_article_body, x_article_content_state, x_article_draft, and x_article_cover_media require x_article_title.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(x_article_title: str_repeat('a', 101)),
        ))->toThrow(InvalidArgumentException::class, 'x_article_title must be 100 characters or fewer.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(poll_options: ['yes']),
        ))->toThrow(InvalidArgumentException::class, 'poll_options must contain between 2 and 4 options.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(poll_options: [str_repeat('a', 26), 'no']),
        ))->toThrow(InvalidArgumentException::class, 'Each X poll option must be 25 characters or fewer.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(poll_duration: 4),
        ))->toThrow(InvalidArgumentException::class, 'poll_duration must be between 5 and 10080 minutes.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(poll_reply_settings: 'following'),
        ))->toThrow(InvalidArgumentException::class, 'poll_reply_settings requires poll_options.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(exclude_reply_user_ids: ['user-1']),
        ))->toThrow(InvalidArgumentException::class, 'exclude_reply_user_ids requires reply_to_id.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Post'),
            options: new PlatformOptions(tagged_user_ids: array_fill(0, 11, 'user')),
        ))->toThrow(InvalidArgumentException::class, 'tagged_user_ids cannot contain more than 10 users.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_cta_type: 'SHOP'),
        ))->toThrow(InvalidArgumentException::class, 'gbp_cta_url is required when gbp_cta_type is set.')
        ->and((new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_cta_type: GoogleBusinessCtaType::Call),
        ))->toArray()['options']['gbp_cta_type'])->toBe('CALL')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_cta_type: GoogleBusinessCtaType::Call, gbp_cta_url: 'https://example.com'),
        ))->toThrow(InvalidArgumentException::class, 'gbp_cta_url must not be set when gbp_cta_type is CALL.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_cta_type: GoogleBusinessCtaType::Call, gbp_cta_url: ' '),
        ))->toThrow(InvalidArgumentException::class, 'gbp_cta_url must not be set when gbp_cta_type is CALL.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_cta_type: 'UNKNOWN', gbp_cta_url: 'https://example.com'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid Google Business CTA type: UNKNOWN.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(linkedin_poll_options: ['Yes']),
        ))->toThrow(InvalidArgumentException::class, 'linkedin_poll_question is required when linkedin_poll_options is set.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(linkedin_poll_question: 'Question?'),
        ))->toThrow(InvalidArgumentException::class, 'linkedin_poll_options is required when linkedin_poll_question is set.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(linkedin_poll_question: 'Question?', linkedin_poll_options: ['Yes']),
        ))->toThrow(InvalidArgumentException::class, 'linkedin_poll_options must contain between 2 and 4 options.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(linkedin_link_url: 'https://example.com', linkedin_poll_question: 'Question?', linkedin_poll_options: ['Yes', 'No']),
        ))->toThrow(InvalidArgumentException::class, 'LinkedIn polls cannot be combined with link previews.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Facebook], title: 'Post'),
            options: new PlatformOptions(facebook_call_to_action: ['type' => 'SHOP_NOW']),
        ))->toThrow(InvalidArgumentException::class, 'facebook_call_to_action requires facebook_link_url or link_url.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Threads], title: 'Post'),
            options: new PlatformOptions(threads_topic_tag: 'bad.tag'),
        ))->toThrow(InvalidArgumentException::class, 'threads_topic_tag must be 50 characters or fewer and cannot contain periods or ampersands.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Threads], title: 'Post'),
            options: new PlatformOptions(threads_reply_control: 'moderators_only'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid threads_reply_control value.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Threads], title: 'Post'),
            options: new PlatformOptions(threads_poll_options: ['Yes']),
        ))->toThrow(InvalidArgumentException::class, 'threads_poll_options must contain between 2 and 4 options.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::Threads], title: 'Post'),
            options: new PlatformOptions(threads_link_attachment: 'ftp://example.com'),
        ))->toThrow(InvalidArgumentException::class, 'threads_link_attachment must be an HTTP(S) URL.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_topic_type: 'UNKNOWN'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid Google Business topic type: UNKNOWN.')
        ->and(fn (): UploadPhotosData => new UploadPhotosData(
            photos: ['https://example.com/photo.jpg'],
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_media_category: 'UNKNOWN'),
        ))->toThrow(InvalidArgumentException::class, 'Invalid Google Business media category: UNKNOWN.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_topic_type: 'EVENT'),
        ))->toThrow(InvalidArgumentException::class, 'gbp_event_title is required for Google Business event posts.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(
                gbp_topic_type: 'EVENT',
                gbp_event_title: 'Launch',
                gbp_event_start_date: '2026/08/01',
                gbp_event_end_date: '2026-08-02',
            ),
        ))->toThrow(InvalidArgumentException::class, 'gbp_event_start_date must use YYYY-MM-DD format.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_upload_to_gallery: true),
        ))->toThrow(InvalidArgumentException::class, 'Google Business gallery uploads require photo media.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post'),
            options: new PlatformOptions(gbp_post_type: 'MEDIA'),
        ))->toThrow(InvalidArgumentException::class, 'Google Business gallery uploads require photo media.')
        ->and(fn (): UploadVideoData => new UploadVideoData(
            video: 'https://example.com/video.mp4',
            common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok], title: 'Post'),
            options: new PlatformOptions(tiktok_location_id: 'location-123'),
        ))->toThrow(InvalidArgumentException::class, 'tiktok_location_name is required when tiktok_location_id is set.');
});

test('platform option validators cover poll and Google Business edge cases', function (): void {
    expect(PlatformOptions::fromArray(['linkedin_poll_duration' => 3])->linkedin_poll_duration)->toBe(3)
        ->and(PlatformOptions::fromArray(['linkedin_poll_duration' => ' '])->linkedin_poll_duration)->toBeNull();

    $threads = new CommonUploadData(user: 'profile', platforms: [Platform::Threads], title: 'Post');

    expect((new UploadTextData(
        common: $threads,
        options: new PlatformOptions(threads_poll_options: ['Yes', 'No']),
    ))->toArray()['options']['threads_poll_options'])->toBe(['Yes', 'No'])
        ->and(fn (): UploadTextData => new UploadTextData(
            common: $threads,
            options: new PlatformOptions(threads_poll_options: [' ', 'No']),
        ))->toThrow(InvalidArgumentException::class, 'Each Threads poll option must be between 1 and 25 characters.');

    $googleBusiness = new CommonUploadData(user: 'profile', platforms: [Platform::GoogleBusiness], title: 'Post');

    expect(fn (): UploadTextData => new UploadTextData(
        common: $googleBusiness,
        options: new PlatformOptions(
            gbp_topic_type: 'EVENT',
            gbp_event_title: 'Launch',
            gbp_event_start_date: '2026-02-30',
            gbp_event_end_date: '2026-03-01',
        ),
    ))->toThrow(InvalidArgumentException::class, 'gbp_event_start_date must be a valid calendar date.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: $googleBusiness,
            options: new PlatformOptions(
                gbp_topic_type: 'EVENT',
                gbp_event_title: 'Launch',
                gbp_event_start_date: '2026-03-01',
                gbp_event_start_time: '25:00',
                gbp_event_end_date: '2026-03-02',
                gbp_event_end_time: '17:00',
            ),
        ))->toThrow(InvalidArgumentException::class, 'gbp_event_start_time must use HH:MM 24-hour format.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(
                linkedin_poll_question: str_repeat('q', 141),
                linkedin_poll_options: ['Yes', 'No'],
            ),
        ))->toThrow(InvalidArgumentException::class, 'linkedin_poll_question must be 140 characters or fewer.')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Post'),
            options: new PlatformOptions(
                linkedin_poll_question: 'Question?',
                linkedin_poll_options: [str_repeat('o', 31), 'No'],
            ),
        ))->toThrow(InvalidArgumentException::class, 'Each LinkedIn poll option must be 30 characters or fewer.');
});

test('LinkedIn poll durations preserve named and numeric API values', function (): void {
    expect((new PlatformOptions(linkedin_poll_duration: LinkedinPollDuration::ThreeDays))->toArray())
        ->toHaveKey('linkedin_poll_duration', 'THREE_DAYS')
        ->and(PlatformOptions::fromArray(['linkedin_poll_duration' => 'FOURTEEN_DAYS'])->toArray())
        ->toHaveKey('linkedin_poll_duration', 'FOURTEEN_DAYS')
        ->and(PlatformOptions::fromArray(['linkedin_poll_duration' => '3'])->toArray())
        ->toHaveKey('linkedin_poll_duration', 3);
});

test('LinkedIn poll duration validates documented values', function (): void {
    $common = new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Poll');
    $options = new PlatformOptions(
        linkedin_poll_question: 'Which option?',
        linkedin_poll_options: ['One', 'Two'],
        linkedin_poll_duration: 'THREE_DAYS',
    );

    $parts = (new UploadTextData(common: $common, options: $options))->toMultipart()->all();

    expect(array_column($parts, 'contents', 'name')['linkedin_poll_duration'])->toBe('THREE_DAYS')
        ->and(fn (): UploadTextData => new UploadTextData(
            common: $common,
            options: new PlatformOptions(linkedin_poll_duration: 'TWO_DAYS'),
        ))->toThrow(InvalidArgumentException::class, 'linkedin_poll_duration must be one of ONE_DAY, THREE_DAYS, SEVEN_DAYS, FOURTEEN_DAYS, 1, 3, 7, or 14.');
});
