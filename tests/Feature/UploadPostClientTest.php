<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Exceptions\UploadPostConnectionException;
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

it('sends idempotency keys with every upload type', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/upload' => $http->response(['request_id' => 'req_video'], 200),
        'https://api.upload-post.com/api/upload_photos' => $http->response(['request_id' => 'req_photos'], 200),
        'https://api.upload-post.com/api/upload_text' => $http->response(['request_id' => 'req_text'], 200),
        'https://api.upload-post.com/api/upload_document' => $http->response(['request_id' => 'req_document'], 200),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    $common = new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Hello');

    $client->uploadVideo(new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: $common,
        idempotency_key: 'idem-video',
    ));
    $client->uploadPhotos(new UploadPhotosData(
        photos: ['https://example.com/photo.jpg'],
        common: $common,
        idempotency_key: 'idem-photos',
    ));
    $client->uploadText(new UploadTextData(
        common: $common,
        idempotency_key: 'idem-text',
    ));
    $client->uploadDocument(new UploadDocumentData(
        document: 'https://example.com/document.pdf',
        user: 'profile',
        title: 'Document',
        idempotency_key: 'idem-document',
    ));

    $http->assertSent(fn ($request): bool => $request->url() === 'https://api.upload-post.com/api/upload'
        && $request->hasHeader('X-Idempotency-Key', 'idem-video'));
    $http->assertSent(fn ($request): bool => $request->url() === 'https://api.upload-post.com/api/upload_photos'
        && $request->hasHeader('X-Idempotency-Key', 'idem-photos'));
    $http->assertSent(fn ($request): bool => $request->url() === 'https://api.upload-post.com/api/upload_text'
        && $request->hasHeader('X-Idempotency-Key', 'idem-text'));
    $http->assertSent(fn ($request): bool => $request->url() === 'https://api.upload-post.com/api/upload_document'
        && $request->hasHeader('X-Idempotency-Key', 'idem-document'));
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

it('can be created with only an api key through make', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'https://api.upload-post.com/api/uploadposts/status*' => $http->response(['status' => 'done'], 200),
    ]);

    $client = UploadPostClient::make('fresh-key', httpFactory: $http);

    expect($client->getStatus('req_123')->status)->toBe('done');

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

it('uses response bodies and unknown fallbacks for api errors', function (): void {
    $data = new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
    );

    $bodyHttp = new HttpFactory;
    $bodyHttp->fake([
        'https://api.upload-post.com/api/upload_text' => $bodyHttp->response('Plain failure', 500),
    ]);

    expect(fn (): UploadResponse => (new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $bodyHttp))->uploadText($data))
        ->toThrow(UploadPostException::class, 'Upload-Post API error [500]: Plain failure');

    $unknownHttp = new HttpFactory;
    $unknownHttp->fake([
        'https://api.upload-post.com/api/upload_text' => $unknownHttp->response('', 422),
    ]);

    expect(fn (): UploadResponse => (new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $unknownHttp))->uploadText($data))
        ->toThrow(UploadPostValidationException::class, 'Upload-Post API error [422]: Unknown API error');
});

it('wraps illuminate connection exceptions', function (): void {
    $http = new HttpFactory;
    $http->fake(fn () => throw new ConnectionException('network down'));

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    expect(fn (): Softgeng\UploadPost\Data\Responses\StatusResponse => $client->getStatus('req_123'))
        ->toThrow(UploadPostConnectionException::class, 'Could not connect to Upload-Post API: network down');
});

it('wraps unexpected request exceptions', function (): void {
    $http = new HttpFactory;
    $http->fake(fn () => throw new RuntimeException('boom'));

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    expect(fn (): Softgeng\UploadPost\Data\Responses\StatusResponse => $client->getStatus('req_123'))
        ->toThrow(UploadPostConnectionException::class, 'Upload-Post request failed: boom');
});
