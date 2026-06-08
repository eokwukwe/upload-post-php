# Upload-Post PHP SDK

PHP SDK for the Upload-Post API, with support for media uploads, scheduling, analytics, and optional Laravel integration.

## Installation

```bash
composer require softgeng/upload-post-php
```

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
));

$request_id = $response->request_id;
```

You can also use the static convenience constructor:

```php
$client = UploadPostClient::make($_ENV['UPLOAD_POST_API_KEY']);
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
        linkedin_visibility: 'PUBLIC',
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
        linkedin_visibility: 'PUBLIC',
    ),
));
```

The SDK accepts local files, remote `http(s)` URLs, `SplFileInfo`, and Laravel/Symfony uploaded file objects for media inputs.

Platform-specific options are only included for platforms selected in `CommonUploadData::$platforms`, matching the official Node SDK behavior.

## Other API Methods

The client also supports:

- `getStatus()` and `getJobStatus()`
- `getHistory()`
- `getAnalytics()`, `getTotalImpressions()`, `getPostAnalytics()`, and `getPlatformMetrics()`
- `listScheduled()`, `editScheduled()`, and `cancelScheduled()`
- `listUsers()`, `createUser()`, `deleteUser()`, `generateJwt()`, and `validateJwt()`
- `getPostComments()`, `replyToComment()`, and `publicReplyToComment()`
- Page/location helpers for Facebook, LinkedIn, Pinterest, and Google Business

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

## Design Notes

- Request DTOs use Upload-Post API snake_case keys directly.
- Response DTOs also expose snake_case properties matching API responses.
- Enums are used for platform and known option values.
- Core SDK uses the standalone Illuminate HTTP client (`illuminate/http`) for retries, timeouts, fakes, JSON helpers, and multipart requests.
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
composer test:analyse
composer test:lint
composer test:refactor
```

Apply formatter or Rector changes intentionally:

```bash
composer lint
composer refactor
```
