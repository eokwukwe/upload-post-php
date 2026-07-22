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
| Reddit | `Platform::Reddit` | Yes | Yes | Yes | No |
| Bluesky | `Platform::Bluesky` | Yes | Yes | Yes | No |
| Discord | `Platform::Discord` | Yes | Yes | Yes | No |
| Telegram | `Platform::Telegram` | Yes | Yes | Yes | No |
| Google Business Profile | `Platform::GoogleBusiness` | Yes | Yes | Yes | No |
| X (Twitter) | `Platform::X` | Yes | Yes | Yes | No |

This table describes the platforms currently implemented by this SDK. The upstream Upload-Post API may add platforms before they are exposed here. Platform-specific account, media, and destination requirements still apply.

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

LinkedIn documents:

```php
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadDocumentData;

$response = $client->uploadDocument(new UploadDocumentData(
    document: __DIR__ . '/deck.pdf',
    user: 'my-profile',
    title: 'Quarterly update',
    description: 'Uploaded from PHP',
    options: new PlatformOptions(
        visibility: 'PUBLIC',
    ),
));
```

The SDK accepts local files, remote `http(s)` URLs, `SplFileInfo`, and Laravel/Symfony uploaded file objects for media inputs.

Platform-specific options are only included for platforms selected in `CommonUploadData::$platforms`, matching the official Node SDK behavior.

All upload DTOs accept an optional `idempotency_key`, which is sent as the `X-Idempotency-Key` header to prevent duplicate uploads when a request is retried.

## Other API Methods

The client also supports:

- `getStatus()` and `getJobStatus()`
- `getHistory()`
- `getAnalytics()`, `getTotalImpressions()`, `getPostAnalytics()`, and `getPlatformMetrics()`
- `listScheduled()`, `editScheduled()`, and `cancelScheduled()`
- `getQueueSettings()`, `updateQueueSettings()`, `getQueuePreview()`, `markQueueSlotFull()`, `unmarkQueueSlotFull()`, and `getNextAvailableSlot()`
- `listUsers()`, `getUser()`, `createUser()`, `deleteUser()`, `generateJwt()`, and `validateJwt()`
- `configureNotifications()` and `configureWebhook()`
- `getPostComments()`, `replyToComment()`, and `publicReplyToComment()`
- Page/location helpers for Facebook, LinkedIn, Pinterest, and Google Business

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

List-style responses expose API-specific names such as `history`, `media`, `comments`, `profiles`, or `scheduled_posts`. They also keep an `items` alias for compatibility with older list-style usage:

```php
$history = $client->getHistory();

foreach ($history->history as $post) {
    // ...
}

$sameItems = $history->items;
```

Current response types:

| Method | Response type | Common fields |
| --- | --- | --- |
| `uploadVideo()`, `uploadPhotos()`, `uploadText()`, `uploadDocument()` | `UploadResponse` | `success`, `request_id`, `job_id`, `status`, `message`, `results` |
| `getStatus()`, `getJobStatus()` | `StatusResponse` | `request_id`, `job_id`, `status`, `completed`, `total`, `results`, `last_update` |
| `getHistory()` | `HistoryResponse` | `history`, `total`, `page`, `limit` |
| `getAnalytics()` | `AnalyticsResponse` | `success`, `data` |
| `getMedia()` | `MediaResponse` | `success`, `media` |
| `listScheduled()` | `ScheduledPostsResponse` | `scheduled_posts` |
| `editScheduled()` | `ScheduledPostResponse` | `success`, `job_id`, `scheduled_date`, `title`, `caption` |
| `getQueueSettings()`, `updateQueueSettings()` | `QueueSettingsResponse` | `success`, `queue_settings` |
| `getQueuePreview()` | `QueuePreviewResponse` | `success`, `timezone`, `max_posts_per_slot`, `slots`, `next_available` |
| `markQueueSlotFull()`, `unmarkQueueSlotFull()` | `QueueSlotFullResponse` | `success`, `message`, `full_slots` |
| `getNextAvailableSlot()` | `QueueNextSlotResponse` | `success`, `next_slot`, `message` |
| `cancelScheduled()`, `deleteUser()`, `validateJwt()`, `replyToComment()`, `publicReplyToComment()`, `selectGoogleBusinessLocation()` | `ActionResponse` | `success`, `message`, `recipient_id`, `message_id` |
| `listUsers()` | `UserProfilesResponse` | `success`, `profiles`, `limit`, `plan` |
| `getUser()`, `createUser()` | `UserResponse` | `success`, `username`, `profile` |
| `generateJwt()` | `JwtResponse` | `success`, `jwt`, `url`, `access_url`, `duration` |
| `configureNotifications()`, `configureWebhook()` | `NotificationConfigResponse` | `success`, `notifications` |
| `getPostComments()` | `CommentsResponse` | `success`, `comments`, `pagination` |
| `getFacebookPages()` | `FacebookPagesResponse` | `success`, `pages` |
| `getLinkedinPages()` | `LinkedinPagesResponse` | `success`, `pages` |
| `getPinterestBoards()` | `PinterestBoardsResponse` | `success`, `boards`, `pinterest_account_used` |
| `getGoogleBusinessLocations()` | `GoogleBusinessLocationsResponse` | `success`, `locations` |

Some helper methods still return `GenericResponse` when the public API schema does not define a dedicated response shape for that endpoint. `GenericResponse` still supports `get()` and `toArray()`.

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
use Softgeng\UploadPost\Facades\UploadPost;

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
use Softgeng\UploadPost\Facades\UploadPost;

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
- Core SDK uses the standalone Illuminate HTTP client (`illuminate/http`) for retries, timeouts, testing fakes, JSON helpers, and multipart requests.
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
