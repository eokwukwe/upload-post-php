<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\Responses\AnalyticsResponse;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Exceptions\UploadPostValidationException;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\UploadPostClient;

it('preserves top-level platform analytics as typed response data', function (): void {
    $http = new HttpFactory;
    $http->fake([
        '*' => $http->response([
            'instagram' => [
                'followers' => 47,
                'reach' => 1250,
            ],
            'youtube' => [
                'followers' => 120,
                'views' => 3400,
            ],
        ]),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);
    $response = $client->getAnalytics('profile', new AnalyticsQueryData(
        platforms: [Platform::Instagram, Platform::YouTube],
    ));

    expect($response)->toBeInstanceOf(AnalyticsResponse::class)
        ->and($response->data['instagram']['followers'])->toBe(47)
        ->and($response->data['youtube']['views'])->toBe(3400)
        ->and($response->toArray())->toBe($response->data);
});

it('requires at least one platform before requesting analytics', function (): void {
    $http = new HttpFactory;
    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    expect(fn () => $client->getAnalytics('profile'))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.')
        ->and(fn () => $client->getAnalytics('profile', new AnalyticsQueryData))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.');

    $http->assertNothingSent();
});

it('surfaces structured errors from status history and analytics endpoints', function (): void {
    $operations = [
        'getStatus' => fn (UploadPostClient $client) => $client->getStatus('req_123'),
        'getJobStatus' => fn (UploadPostClient $client) => $client->getJobStatus('job_123'),
        'getHistory' => fn (UploadPostClient $client) => $client->getHistory(),
        'getAnalytics' => fn (UploadPostClient $client) => $client->getAnalytics(
            'profile',
            new AnalyticsQueryData(platforms: [Platform::Instagram]),
        ),
    ];

    foreach ($operations as $method => $operation) {
        $http = new HttpFactory;
        $http->fake([
            '*' => $http->response([
                'detail' => [[
                    'loc' => ['query', 'request_id'],
                    'msg' => 'Invalid identifier',
                    'type' => 'value_error',
                ]],
            ], 422),
        ]);

        $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

        try {
            $operation($client);
            test()->fail("{$method} did not throw an exception.");
        } catch (UploadPostValidationException $exception) {
            expect($exception->getMessage())->toContain(
                'Upload-Post API error [422]: [{"loc":["query","request_id"],"msg":"Invalid identifier","type":"value_error"}]'
            )->and($exception->payload)->toBe([
                'detail' => [[
                    'loc' => ['query', 'request_id'],
                    'msg' => 'Invalid identifier',
                    'type' => 'value_error',
                ]],
            ]);
        }
    }
});

it('does not cast structured status fields to strings', function (): void {
    $http = new HttpFactory;
    $http->fake([
        '*' => $http->response([
            'request_id' => ['unexpected' => 'shape'],
            'status' => ['unexpected' => 'shape'],
            'results' => [],
        ]),
    ]);

    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);
    $response = $client->getStatus('req_123');

    expect($response->request_id)->toBeNull()
        ->and($response->status)->toBeNull()
        ->and($response->raw['request_id'])->toBe(['unexpected' => 'shape']);
});
