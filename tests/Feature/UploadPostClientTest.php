<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Exceptions\UploadPostException;
use Softgeng\UploadPost\Exceptions\UploadPostValidationException;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\UploadPostClient;

it('posts text upload as multipart', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/upload_text' => $http->response(['request_id' => 'req_123', 'status' => 'queued'], 200),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    $response = $client->uploadText(new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello', async_upload: true),
        link_url: 'https://example.com',
    ));

    expect($response->request_id)->toBe('req_123');

    $http->assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Apikey test'));
});

it('uses the explicit api key when make receives an existing config', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/uploadposts/status*' => $http->response(['status' => 'done'], 200),
    ]);

    $client = UploadPostClient::make('fresh-key', new UploadPostConfig(apiKey: 'stale-key'), $http);

    $client->getStatus('req_123');

    $http->assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Apikey fresh-key'));
});

it('throws a validation exception for 422 responses by default', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/upload_text' => $http->response(['message' => 'Invalid payload'], 422),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    expect(fn (): UploadResponse => $client->uploadText(new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
    )))->toThrow(UploadPostValidationException::class, 'Upload-Post API error [422]: Invalid payload');
});

it('can throw the base exception for 422 responses when validation exceptions are disabled', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/upload_text' => $http->response(['message' => 'Invalid payload'], 422),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test', throwOnValidation: false), $http);

    expect(fn (): UploadResponse => $client->uploadText(new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
    )))->toThrow(UploadPostException::class, 'Upload-Post API error [422]: Invalid payload');
});
