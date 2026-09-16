<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\Responses\ActionResponse;
use Softgeng\UploadPost\Data\Responses\AnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\HistoryResponse;
use Softgeng\UploadPost\Data\Responses\MediaResponse;
use Softgeng\UploadPost\Data\Responses\PostAnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\TotalImpressionsResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
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

    expect(fn (): AnalyticsResponse => $client->getAnalytics('profile'))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.')
        ->and(fn (): AnalyticsResponse => $client->getAnalytics('profile', new AnalyticsQueryData))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.')
        ->and(fn (): AnalyticsResponse => $client->getAnalytics('profile', new AnalyticsQueryData(platforms: [''])))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.')
        ->and(fn (): AnalyticsResponse => $client->getAnalytics('profile', new AnalyticsQueryData(platforms: ['   '])))
        ->toThrow(InvalidArgumentException::class, 'At least one analytics platform is required.');

    $http->assertNothingSent();
});

it('rejects blank identifiers before requesting read and schedule endpoints', function (): void {
    $http = new HttpFactory;
    $http->fake(['*' => $http->response(['success' => true])]);
    $client = new UploadPostClient(new UploadPostConfig(apiKey: 'test'), $http);

    $operations = [
        'request_id for status' => [
            fn (): StatusResponse => $client->getStatus(' '),
            'request_id',
        ],
        'job_id for status' => [
            fn (): StatusResponse => $client->getJobStatus(''),
            'job_id',
        ],
        'profileUsername for analytics' => [
            fn (): AnalyticsResponse => $client->getAnalytics(' ', new AnalyticsQueryData(platforms: [Platform::Instagram])),
            'profileUsername',
        ],
        'profileUsername for total impressions' => [
            fn (): TotalImpressionsResponse => $client->getTotalImpressions(''),
            'profileUsername',
        ],
        'request_id for post analytics' => [
            fn (): PostAnalyticsResponse => $client->getPostAnalytics("\t"),
            'request_id',
        ],
        'platform_post_id for post analytics' => [
            fn (): PostAnalyticsResponse => $client->getPostAnalyticsByPlatformId('', 'instagram', 'profile'),
            'platform_post_id',
        ],
        'platform for post analytics' => [
            fn (): PostAnalyticsResponse => $client->getPostAnalyticsByPlatformId('post', ' ', 'profile'),
            'platform',
        ],
        'user for post analytics' => [
            fn (): PostAnalyticsResponse => $client->getPostAnalyticsByPlatformId('post', 'instagram', ''),
            'user',
        ],
        'platform for media' => [
            fn (): MediaResponse => $client->getMedia('', 'profile'),
            'platform',
        ],
        'user for media' => [
            fn (): MediaResponse => $client->getMedia('instagram', ' '),
            'user',
        ],
        'job_id for cancellation' => [
            fn (): ActionResponse => $client->cancelScheduled(''),
            'job_id',
        ],
        'job_id for editing' => [
            fn (): ActionResponse => $client->editScheduled(' ', title: 'Updated'),
            'job_id',
        ],
        'jwt' => [
            fn (): UserResponse => $client->validateJwt("\n"),
            'jwt',
        ],
    ];

    foreach ($operations as [$operation, $field]) {
        expect($operation)->toThrow(InvalidArgumentException::class, "{$field} is required.");
    }

    $http->assertNothingSent();
});

it('surfaces structured errors from status history and analytics endpoints', function (): void {
    $operations = [
        'getStatus' => fn (UploadPostClient $client): StatusResponse => $client->getStatus('req_123'),
        'getJobStatus' => fn (UploadPostClient $client): StatusResponse => $client->getJobStatus('job_123'),
        'getHistory' => fn (UploadPostClient $client): HistoryResponse => $client->getHistory(),
        'getAnalytics' => fn (UploadPostClient $client): AnalyticsResponse => $client->getAnalytics(
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
