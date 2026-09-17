# upload-post-php SDK

[![Tests](https://github.com/eokwukwe/upload-post-php/actions/workflows/tests.yml/badge.svg)](https://github.com/eokwukwe/upload-post-php/actions/workflows/tests.yml)
[![Coverage](https://codecov.io/github/eokwukwe/upload-post-php/graph/badge.svg?token=A99S78ONJ7)](https://codecov.io/github/eokwukwe/upload-post-php)

PHP SDK for the Upload-Post API, with support for media uploads, scheduling, analytics, and optional Laravel integration.

## Installation

### Requirements

- PHP 8.2 or higher
- Composer

Install the package with Composer:

```bash
composer require softgeng/upload-post-php
```

To install directly from GitHub before a tagged release is available, add the repository to your application's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/eokwukwe/upload-post-php.git"
    }
  ],
  "require": {
    "softgeng/upload-post-php": "dev-main"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

## Supported Platforms

The SDK currently exposes the following social platforms through the `Platform` enum:

| Platform | Enum case | Video | Photos | Text | Documents |
| --- | --- | :---: | :---: | :---: | :---: |
| TikTok | `Platform::TikTok` | Yes | Yes | No | No |
| Instagram | `Platform::Instagram` | Yes | Yes | No | No |
| YouTube | `Platform::YouTube` | Yes | No | No | No |
| LinkedIn | `Platform::LinkedIn` | Yes | Yes | Yes | Yes |
| Facebook | `Platform::Facebook` | Yes | Yes | Yes | No |
| Pinterest | `Platform::Pinterest` | Yes | Yes | No | No |
| Threads | `Platform::Threads` | Yes | Yes | Yes | No |
| Reddit | `Platform::Reddit` | Unavailable | Unavailable | Unavailable | No |
| Bluesky | `Platform::Bluesky` | Yes | Yes | Yes | No |
| Discord | `Platform::Discord` | Yes | Yes | Yes | No |
| Telegram | `Platform::Telegram` | Yes | Yes | Yes | No |
| Google Business Profile | `Platform::GoogleBusiness` | Yes | Yes | Yes | No |
| X (Twitter) | `Platform::X` | Yes | Yes | Yes | No |

This table describes the platforms currently implemented by this SDK. The upstream Upload-Post API may add platforms before they are exposed here. Platform-specific account, media, and destination requirements still apply.

> Reddit publishing is temporarily unavailable in the Upload-Post API. The SDK rejects Reddit in every upload DTO before making a request; `Platform::Reddit` remains available for history and analytics.

## Plain PHP Usage

```php
use Softgeng\UploadPost\UploadPostClient;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Enums\Platform;

$client = new UploadPostClient(new UploadPostConfig(
    apiKey: $_ENV['UPLOAD_POST_API_KEY'],
));

$response = $client->uploadVideo(new UploadVideoData(
    video: __DIR__ . '/video.mp4',
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::TikTok, Platform::Instagram],
        title: 'Uploaded from PHP',
        description: 'Standalone PHP usage',
        async_upload: true,
    ),
    idempotency_key: 'unique-upload-key',
));

$request_id = $response->request_id;
```

Use `external_id` to correlate an upload with a record in your own system. It is distinct from `request_id`, which identifies the API request:

```php
$common = new CommonUploadData(
    user: 'my-profile',
    platforms: [Platform::Instagram],
    title: 'Uploaded from PHP',
    external_id: 'post_123',
    is_ai_generated: true,
);
```

Platform-specific API options are explicit, typed `PlatformOptions` properties. Unsupported platform/type combinations are deliberately omitted from the request:

```php
$options = new PlatformOptions(
    tiktok_music_id: 'music-123',
    tiktok_location_id: 'location-456',
    tiktok_location_name: 'Lekki Conservation Centre',
);
```

Pinterest AI disclosures accept the documented disclosure values as a string or list:

```php
$options = new PlatformOptions(
    pinterest_ai_disclosures: ['AI_MODIFIED'],
);
```

You can also use the static convenience constructor:

```php
$client = UploadPostClient::make($_ENV['UPLOAD_POST_API_KEY']);
```

Request DTOs can also be created from arrays, which is useful when building them from validated request data:

```php
$response = $client->uploadVideo(UploadVideoData::fromArray([
    'video' => $request->file('video'),
    'user' => $request->string('user')->toString(),
    'platforms' => $request->input('platforms', []),
    'title' => $request->string('title')->toString(),
    'async_upload' => true,
    'idempotency_key' => $request->header('X-Idempotency-Key'),
]));
```

DTOs can be converted back to arrays when you need to store, inspect, or transform request data:

```php
$payload = UploadVideoData::fromArray($validated)->toArray();
```

## Upload Examples

Text posts:

```php
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Enums\Platform;

$response = $client->uploadText(new UploadTextData(
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::X, Platform::LinkedIn],
        title: 'Posted from PHP',
        async_upload: true,
    ),
    link_url: 'https://example.com',
    options: new PlatformOptions(
        x_long_text_as_post: true,
        visibility: 'PUBLIC',
    ),
));
```

Photo posts:

```php
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Enums\Platform;

$response = $client->uploadPhotos(new UploadPhotosData(
    photos: [
        __DIR__ . '/photo-1.jpg',
        'https://example.com/photo-2.jpg',
    ],
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::Instagram, Platform::Facebook],
        title: 'Photo post',
    ),
));
```

TikTok photo settings and Facebook photo media types are configured through `PlatformOptions`. Options are serialized only when their platform is selected:

```php
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Enums\FacebookMediaType;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Enums\TiktokPostMode;
use Softgeng\UploadPost\Enums\TiktokPrivacyLevel;

$response = $client->uploadPhotos(new UploadPhotosData(
    photos: [__DIR__ . '/photo.jpg'],
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::TikTok, Platform::Facebook],
        title: 'Photo post',
    ),
    options: new PlatformOptions(
        privacy_level: TiktokPrivacyLevel::PublicToEveryone,
        post_mode: TiktokPostMode::DirectPost,
        facebook_media_type: FacebookMediaType::Posts,
    ),
));
```

For a Google Business Profile gallery upload, use the photo endpoint and set either `gbp_post_type` or `gbp_upload_to_gallery`:

```php
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Enums\Platform;

$response = $client->uploadPhotos(new UploadPhotosData(
    photos: [__DIR__ . '/storefront.jpg'],
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::GoogleBusiness],
        title: 'Our storefront',
    ),
    options: new PlatformOptions(
        gbp_location_id: 'accounts/123456789/locations/222222222',
        gbp_post_type: 'MEDIA',
        gbp_media_category: 'EXTERIOR',
    ),
));
```

Google Business option values are available through `GoogleBusinessCtaType`, `GoogleBusinessTopicType`, `GoogleBusinessPostType`, and `GoogleBusinessMediaCategory`. Every CTA except `CALL` requires `gbp_cta_url`; `CALL` must omit it:

```php
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Enums\GoogleBusinessCtaType;

$options = new PlatformOptions(
    gbp_cta_type: GoogleBusinessCtaType::LearnMore,
    gbp_cta_url: 'https://example.com/learn-more',
);
```

YouTube subtitles are supported on video uploads. Every track requires a BCP-47 language code and a local or uploaded subtitle file; subtitle URLs are not accepted by `YoutubeSubtitleData` because the API documents subtitle inputs as multipart files.

```php
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
use Softgeng\UploadPost\Enums\Platform;

$response = $client->uploadVideo(new UploadVideoData(
    video: __DIR__ . '/video.mp4',
    common: new CommonUploadData(
        user: 'my-profile',
        platforms: [Platform::YouTube],
        title: 'Product walkthrough',
    ),
    options: new PlatformOptions(
        youtube_subtitles: [
            new YoutubeSubtitleData(
                language: 'en',
                file: __DIR__ . '/subtitles-en.srt',
                name: 'English',
            ),
        ],
    ),
));
```

LinkedIn documents:

```php
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadDocumentData;

$response = $client->uploadDocument(new UploadDocumentData(
    document: __DIR__ . '/deck.pdf',
    user: 'my-profile',
    title: 'Quarterly update',
    description: 'Uploaded from PHP',
    first_comment: 'Questions are welcome below.',
    linkedin_first_comment: 'Download the supporting resources from our website.',
    options: new PlatformOptions(
        visibility: 'PUBLIC',
    ),
));
```

The `video` input and every item in the `photos` array accept local file path strings, `SplFileInfo`, Laravel/Symfony uploaded file objects, or public `http(s)` URLs. Document uploads accept the same inputs.

File-only inputs—`YoutubeSubtitleData::$file`, Instagram's `cover_image`, TikTok's `tiktok_cover_image`, LinkedIn's `linkedin_subtitles`, and `first_comment_media`—accept local file path strings, `SplFileInfo`, and Laravel/Symfony uploaded file objects, but not URLs. For URL variants, use `cover_url`, `tiktok_cover_image_url`, or `linkedin_subtitles_url` respectively.

Platform-specific fields are only included when their platform is selected in `CommonUploadData::$platforms`. They are also filtered by upload type so options can be safely reused:

| Option | Emitted for |
| --- | --- |
| `privacy_level`, `post_mode` | TikTok video and photo uploads |
| `facebook_media_type` | Facebook videos (`REELS`, `STORIES`, `VIDEO`) and photos (`POSTS`, `STORIES`) |
| `threads_long_text_as_post` | Threads text uploads |
| `threads_thread_media_layout` | Threads photo uploads |
| `threads_topic_tag` | Threads video, photo, and text uploads |
| `x_thread_image_layout` | X photo uploads |
| `gbp_post_type`, `gbp_upload_to_gallery`, `gbp_media_category` | Google Business media uploads; use the photo endpoint for Media/Gallery publishing |

Video, photo, and text upload DTOs accept an optional `idempotency_key`, which is sent as the `X-Idempotency-Key` header to prevent duplicate uploads when a request is retried.

## Request Validation

Request DTOs validate deterministic API requirements during construction and multipart preparation. Invalid data throws PHP's `InvalidArgumentException` before an HTTP request is sent.

The SDK currently enforces these upload requirements:

- `user` and at least one non-blank platform are required for video, photo, and text uploads.
- Text uploads require `title` and support LinkedIn, Facebook, Threads, Bluesky, Discord, Telegram, Google Business Profile, and X.
- YouTube video uploads require `title`.
- Reddit is rejected for video, photo, and text uploads while the upstream API returns `reddit_unavailable`.
- Pinterest video and photo uploads require `pinterest_board_id`.
- Photo uploads require at least one photo and do not support YouTube.
- Document uploads always target LinkedIn and require `user`, `title`, and `document`.
- `scheduled_date` cannot be combined with `add_to_queue`.
- `first_comment_media` is unavailable while Reddit uploads are unavailable.

The SDK also validates documented option relationships, including upload-compatible Instagram and Facebook media types, X polls and Article exclusions, LinkedIn poll requirements, Facebook CTA links, YouTube region restrictions, Pinterest cover pairs, and Google Business CTA, event, and gallery requirements. X video uploads support both URL-based and inline subtitles.

Some validation still necessarily happens at the API. Rules that depend on live account state—such as connected accounts, page access, provider permissions, current platform limits, and media processing—are checked after the request reaches Upload-Post. An API `422` response is exposed as `Softgeng\UploadPost\Exceptions\UploadPostValidationException`, which includes the HTTP status and response payload.

## Other API Methods

The client also supports:

- `getStatus()` and `getJobStatus()`
- `getHistory()` (use `HistoryQueryData` for platform, status, profile, request/job, external-id, and date filters)
- `getAnalytics()`, `getTotalImpressions()`, `getPostAnalytics()` (optionally filtered by platform), and `getPlatformMetrics()`
- `getTikTokTrendingMusic()`, `searchTikTokMusic()`, and `getTikTokLocations()` for TikTok music and place pickers
- `listScheduled()` (use `ScheduledPostsQueryData` for profile, date-range, limit, and offset filters), `editScheduled()`, and `cancelScheduled()`
- `getQueueSettings()`, `updateQueueSettings()`, `getQueuePreview()`, `markQueueSlotFull()`, `unmarkQueueSlotFull()`, and `getNextAvailableSlot()`
- `listUsers()`, `getUser()`, `createUser()`, `deleteUser()`, `generateJwt()`, and `validateJwt()`
- `getUserPreferences()`, `updateUserPreferences()`, `getNotificationConfig()`, `updateNotificationConfig()`, `deleteNotificationConfig()`, `configureNotifications()`, and `configureWebhook()`
- `getPostComments()`, `createComment()`, `deleteComment()`, and `actOnComment()`
- Page/location helpers for Facebook, LinkedIn, Pinterest, and Google Business

### Comments

Comments use the same platform-aware API for Instagram, Facebook, YouTube, LinkedIn, TikTok, X, Threads, and Bluesky. Pass a request DTO so the SDK can validate the documented target rules before making a request:

```php
use Softgeng\UploadPost\Data\CommentActionData;
use Softgeng\UploadPost\Data\CommentQueryData;
use Softgeng\UploadPost\Data\CreateCommentData;
use Softgeng\UploadPost\Enums\Platform;

$comments = $client->getPostComments(new CommentQueryData(
    user: 'profile',
    platform: Platform::TikTok,
    post_id: '7401234567890123456',
    comment_id: '7401234567890999888', // Optional: list replies below this TikTok comment.
));

$created = $client->createComment(new CreateCommentData(
    user: 'profile',
    platform: Platform::TikTok,
    post_id: '7401234567890123456',
    comment_id: '7401234567890999888', // Optional: makes this a reply.
    message: 'Thanks!',
));

$moderated = $client->actOnComment(new CommentActionData(
    user: 'profile',
    platform: Platform::TikTok,
    comment_id: '7401234567890999888',
    post_id: '7401234567890123456',
    action: 'hide',
));
```

`CreateCommentData` requires `comment_id` for Instagram, `post_id` for TikTok, and exactly one target for the other supported platforms. `DeleteCommentData` requires `post_id` for LinkedIn. `CommentActionData` accepts only the moderation verbs supported by the selected platform, including Facebook edits, YouTube hold/ban, Instagram comment enablement, and Threads approval. Comment mutations return `ActionResponse`; its typed `id`, `platform`, `action`, `comment_id`, and TikTok `result` fields complement the existing `raw` response payload.

## Webhook Notifications

You can configure Upload-Post notifications programmatically. For the common webhook-only case, use `configureWebhook()`:

```php
$response = $client->configureWebhook('https://example.com/upload-post/webhook');

$configured = $response->success;
$notifications = $response->notifications;
```

All webhook events are enabled by default: `upload_completed`, `social_account_connected`, `social_account_disconnected`, and `social_account_reauth_required`.

Use the `WebhookEvent` enum when you want autocomplete for event names or need to disable a specific event:

```php
use Softgeng\UploadPost\Enums\WebhookEvent;

$response = $client->configureWebhook('https://example.com/upload-post/webhook', [
    WebhookEvent::SocialAccountReauthRequired->value => false,
]);
```

For the full notification payload, including Telegram, use `NotificationConfigData`:

```php
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Enums\WebhookEvent;

$response = $client->configureNotifications(new NotificationConfigData(
    webhook: true,
    telegram: false,
    webhook_url: 'https://example.com/upload-post/webhook',
    telegram_chat_id: '123456789',
    webhook_events: [
        WebhookEvent::UploadCompleted->value => true,
        WebhookEvent::SocialAccountConnected->value => true,
    ],
));
```

## Response Objects

Client methods return typed response DTOs instead of plain arrays. The DTO properties use the same snake_case field names returned by the Upload-Post API, and the original payload is still available when you need it:

```php
$response = $client->uploadVideo($data);

$request_id = $response->request_id;
$job_id = $response->job_id;
$raw = $response->toArray();
$status = $response->get('status');
```

List-style responses expose API-specific shaped collections such as `history`, `media`, `comments`, `profiles`, or `scheduled_posts`. Each item retains its provider payload in `raw`:

```php
$history = $client->getHistory();

foreach ($history->history as $post) {
    echo $post->platform;
    echo $post->post_url;
    $providerFields = $post->raw;
}
```

The endpoint response wrappers live in `Softgeng\UploadPost\Data\Responses`; their reusable item/value DTOs live in `Softgeng\UploadPost\Data`. These include `PlatformUploadResult`, `CommentData`, `MediaData`, `HistoryItemData`, `ScheduledPostData`, `UserProfileData`, `ResourceData`, and `QueueSlotData`. `PlatformUploadResult` exposes publish outcome fields including `post_url`, `skipped`, `skip_reason`, `failure_stage`, `thumbnail_error`, `restriction_reason`, `restricted_until`, `retry_after_seconds`, `fallback_to_inbox`, media-processing metadata, and first-comment status; upload responses expose typed usage limits. History responses expose `in_progress`, history items preserve mixed-type `dashboard` values and expose `external_id`/`fallback_to_inbox`, and scheduled responses expose pagination metadata plus item preview, source, platform-content, and timezone fields. Comment and media pagination is a `PaginationData` object. This keeps documented fields discoverable while preserving `raw` for fields that vary by platform or evolve over time.

Current response types:

| Method | Response type | Common fields |
| --- | --- | --- |
| `uploadVideo()`, `uploadPhotos()`, `uploadText()`, `uploadDocument()` | `UploadResponse` | `success`, `request_id`, `external_id`, `job_id`, `scheduled_date`, `total_platforms`, `status`, `message`, `results`, `usage`, `warnings` |
| `getStatus()`, `getJobStatus()` | `StatusResponse` | `request_id`, `external_id`, `job_id`, `status`, `message`, `completed`, `total`, `results`, `last_update` |
| `getHistory()` | `HistoryResponse` | `history`, `in_progress`, `total`, `page`, `limit` |
| `getAnalytics()` | `AnalyticsResponse` | `success`, `data` |
| `getPostAnalytics()`, `getPostAnalyticsByPlatformId()` | `PostAnalyticsResponse` | `success`, `post`, `platforms` |
| `getPlatformMetrics()` | `PlatformMetricsResponse` | `platforms` (each exposes `primary_impressions_field`, `available_metrics`, `metric_labels`) |
| `getTotalImpressions()` | `TotalImpressionsResponse` | `success`, `profile_username`, `start_date`, `end_date`, `total_impressions`, `metrics`, `per_platform`, `per_day`, `platforms_filter` |
| `getTikTokTrendingMusic()`, `searchTikTokMusic()` | `TikTokMusicResponse` | `success`, typed tracks, and search catalog metadata |
| `getTikTokLocations()` | `TikTokLocationsResponse` | `success`, `query`, and typed location results |
| `getMedia()` | `MediaResponse` | `success`, `media` |
| `listScheduled()` | `ScheduledPostsResponse` | `scheduled_posts`, `total`, `limit`, `offset` |
| `editScheduled()` | `ScheduledPostResponse` | `success`, `job_id`, `external_id`, `scheduled_date`, `title`, `caption` |
| `getQueueSettings()`, `updateQueueSettings()` | `QueueSettingsResponse` | `success`, `queue_settings` (`timezone`, `slots`, `days_of_week`, `max_posts_per_slot`, `full_slots`) |
| `getQueuePreview()` | `QueuePreviewResponse` | `success`, `timezone`, `max_posts_per_slot`, `slots`, `next_available`; each slot exposes `scheduled_posts` and the legacy `scheduled_post` when present |
| `markQueueSlotFull()`, `unmarkQueueSlotFull()` | `QueueSlotFullResponse` | `success`, `message`, `full_slots` |
| `getNextAvailableSlot()` | `QueueNextSlotResponse` | `success`, `next_slot`, `message` |
| `cancelScheduled()`, `deleteUser()`, `createComment()`, `deleteComment()`, `actOnComment()`, `selectGoogleBusinessLocation()`, `clearGoogleBusinessLocation()`, `selectFacebookPage()`, `clearFacebookPage()`, `selectLinkedinPage()`, `clearLinkedinPage()` | `ActionResponse` | `success`, `message`, `credits_refunded`, `recipient_id`, `message_id`, `id`, `platform`, `action`, `comment_id`, `gbp_location_id`, `gbp_location_name`, `facebook_page_id`, `facebook_page_name`, `linkedin_page_id`, `linkedin_page_name`, `result` |
| `listUsers()` | `UserProfilesResponse` | `success`, `profiles`, `limit`, `plan` |
| `getUser()`, `createUser()`, `validateJwt()` | `UserResponse` | `success`, `username`, `profile` |
| `generateJwt()` | `JwtResponse` | `success`, `jwt`, `url`, `access_url`, `duration` |
| `configureNotifications()`, `configureWebhook()`, `getNotificationConfig()`, `updateNotificationConfig()`, `deleteNotificationConfig()` | `NotificationConfigResponse` | `success`, `notifications` |
| `getUserPreferences()`, `updateUserPreferences()` | `UserPreferencesResponse` | `success`, `preferences.week_start_day` |
| `getPostComments()` | `CommentsResponse` | `success`, `comments`, `pagination` |
| `getFacebookPages()` | `FacebookPagesResponse` | `success`, `pages` |
| `getFacebookPage()` | `FacebookPagesResponse` | `success`, `pages`, `selected_page_id`, `selected_page_name` |
| `getLinkedinPages()` | `LinkedinPagesResponse` | `success`, `pages` |
| `getLinkedinPage()` | `LinkedinPagesResponse` | `success`, `pages`, `selected_page_id`, `selected_page_name` |
| `getPinterestBoards()` | `PinterestBoardsResponse` | `success`, `boards`, `pinterest_account_used` |
| `getGoogleBusinessLocations()` | `GoogleBusinessLocationsResponse` | `success`, `locations` |
| `getGoogleBusinessLocation()` | `GoogleBusinessLocationsResponse` | `success`, `locations`, `selected_location_id`, `selected_location_name` |

`GenericResponse` remains available for custom/raw integrations. Analytics, platform metrics, and total impressions expose dedicated response DTOs while retaining `raw` access through `get()` and `toArray()`.

## Laravel Usage

Publish config:

```bash
php artisan vendor:publish --tag=upload-post-config
```

Add your key:

```env
UPLOAD_POST_API_KEY=your-api-key
```

Inject the client:

```php
use Softgeng\UploadPost\UploadPostClient;

public function store(UploadPostClient $uploadPost)
{
    $status = $uploadPost->getStatus('request-id');
}
```

Or use the facade:

```php
use Softgeng\UploadPost\Laravel\Facades\UploadPost;

$status = UploadPost::getStatus('request-id');
```

## Testing

Use the built-in fake to test code that calls Upload-Post without sending real API requests.

Plain PHP:

```php
use Softgeng\UploadPost\UploadPostClient;

$fake = UploadPostClient::fake([
    '/uploadposts/status*' => [
        'request_id' => 'req_123',
        'status' => 'done',
    ],
]);

$status = $fake->client()->getStatus('req_123');

$fake->assertSent('/uploadposts/status', 'GET');
```

Laravel facade:

```php
use Softgeng\UploadPost\Laravel\Facades\UploadPost;

$fake = UploadPost::fake([
    '/upload_text' => [
        'request_id' => 'req_123',
        'status' => 'queued',
    ],
]);

UploadPost::uploadText($data);

$fake->assertSent('/upload_text', 'POST');
```

Custom status codes can be faked with `UploadPostFake::response()`:

```php
use Softgeng\UploadPost\Testing\UploadPostFake;
use Softgeng\UploadPost\UploadPostClient;

$fake = UploadPostClient::fake([
    '/upload_text' => UploadPostFake::response(['message' => 'Invalid payload'], 422),
]);
```

Fake response keys can be full URLs, endpoint paths like `/upload_text`, endpoint patterns like `/uploadposts/status*`, or `*` for a catch-all response.

## Design Notes

- Request DTOs use Upload-Post API snake_case keys directly.
- Response DTOs also expose snake_case properties matching API responses.
- Enums are used for platform and known option values.
- `GenerateJwtData::$language` accepts `JwtLanguage` values (or the equivalent supported strings).
- Core SDK uses the standalone Illuminate HTTP client (`illuminate/http`) for retries, timeouts, testing fakes, JSON helpers, and multipart requests.
- Automatic transport retries apply to read requests and uploads with an idempotency key; JSON mutations are sent once so callers can decide how to reconcile and retry ambiguous outcomes.
- It works in plain PHP without a Laravel application; Laravel support is optional and lives in the service provider/facade.

## Development

Install dependencies:

```bash
composer install
```

Run the full quality gate:

```bash
composer codecheck
```

Individual checks:

```bash
composer test
composer test:coverage
composer test:analyse
composer test:lint
composer test:refactor
```

Apply formatter or Rector changes intentionally:

```bash
composer lint
composer refactor
```
