# Upload-Post PHP SDK

A production-ready standalone PHP SDK for the Upload-Post API, with optional Laravel auto-discovery support.

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
