<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Exceptions\UploadPostValidationException;
use Softgeng\UploadPost\Facades\UploadPost;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\Testing\UploadPostFake;
use Softgeng\UploadPost\UploadPostClient;

it('fakes api calls for plain PHP clients', function (): void {
    $fake = UploadPostClient::fake([
        '/uploadposts/status*' => [
            'request_id' => 'req_123',
            'status' => 'done',
        ],
    ]);

    $response = $fake->client()->getStatus('req_123');

    expect($response->request_id)->toBe('req_123')
        ->and($response->status)->toBe('done');

    $fake
        ->assertSent('/uploadposts/status', 'GET')
        ->assertSentCount(1)
        ->assertNotSent('/uploadposts/users');
});

it('fakes api calls through the Laravel facade', function (): void {
    $fake = UploadPost::fake([
        'uploadposts/status*' => [
            'request_id' => 'req_456',
            'status' => 'queued',
        ],
    ]);

    $response = UploadPost::getStatus('req_456');

    expect($response->request_id)->toBe('req_456')
        ->and($response->status)->toBe('queued')
        ->and(app(UploadPostClient::class))->toBe($fake->client());

    $fake->assertSent(
        fn ($request): bool => $request->hasHeader('Authorization', 'Apikey test-key')
    );
});

it('allows fake error responses to exercise SDK exceptions', function (): void {
    $fake = UploadPostClient::fake([
        '/upload_text' => UploadPostFake::response(['message' => 'Invalid payload'], 422),
    ]);

    expect(fn (): Softgeng\UploadPost\Data\Responses\UploadResponse => $fake->client()->uploadText(new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
    )))->toThrow(UploadPostValidationException::class, 'Upload-Post API error [422]: Invalid payload');

    $fake->assertSent('/upload_text', 'POST');
});

it('supports callable fakes and callable not sent assertions', function (): void {
    $fake = UploadPostClient::fake(
        fn (): mixed => UploadPostFake::response([
            'request_id' => 'req_callable',
            'status' => 'done',
        ])
    );

    expect($fake->http())->toBeInstanceOf(Illuminate\Http\Client\Factory::class)
        ->and($fake->client()->getStatus('req_callable')->status)->toBe('done');

    $fake
        ->assertSent('/uploadposts/status')
        ->assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/uploadposts/users'));
});

it('can assert that no fake requests were sent', function (): void {
    UploadPostClient::fake()->assertNothingSent();
});

it('matches full URL and leading wildcard fake patterns', function (): void {
    $wildcardFake = UploadPostClient::fake([
        '*/uploadposts/status*' => [
            'request_id' => 'req_wildcard',
            'status' => 'wildcard',
        ],
    ]);

    expect($wildcardFake->client()->getStatus('req_wildcard')->status)->toBe('wildcard');

    $fullUrlFake = UploadPostClient::fake([
        'https://fake.upload-post.test/api/uploadposts/status*' => [
            'request_id' => 'req_full_url',
            'status' => 'full-url',
        ],
    ], new UploadPostConfig(
        apiKey: 'fake-key',
        baseUrl: 'https://fake.upload-post.test/api',
    ));

    expect($fullUrlFake->client()->getStatus('req_full_url')->status)->toBe('full-url');

    $fullUrlFake
        ->assertSent('https://fake.upload-post.test/api/uploadposts/status?request_id=req_full_url')
        ->assertNotSent('/uploadposts/status', 'POST');
});
