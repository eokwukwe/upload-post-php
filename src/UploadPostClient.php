<?php

declare(strict_types=1);

namespace Softgeng\UploadPost;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Data\Responses\ActionResponse;
use Softgeng\UploadPost\Data\Responses\AnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\CommentsResponse;
use Softgeng\UploadPost\Data\Responses\FacebookPagesResponse;
use Softgeng\UploadPost\Data\Responses\GenericResponse;
use Softgeng\UploadPost\Data\Responses\GoogleBusinessLocationsResponse;
use Softgeng\UploadPost\Data\Responses\HistoryResponse;
use Softgeng\UploadPost\Data\Responses\JwtResponse;
use Softgeng\UploadPost\Data\Responses\LinkedinPagesResponse;
use Softgeng\UploadPost\Data\Responses\MediaResponse;
use Softgeng\UploadPost\Data\Responses\NotificationConfigResponse;
use Softgeng\UploadPost\Data\Responses\PinterestBoardsResponse;
use Softgeng\UploadPost\Data\Responses\QueueNextSlotResponse;
use Softgeng\UploadPost\Data\Responses\QueuePreviewResponse;
use Softgeng\UploadPost\Data\Responses\QueueSettingsResponse;
use Softgeng\UploadPost\Data\Responses\QueueSlotFullResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostsResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\Responses\UserProfilesResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Exceptions\UploadPostConnectionException;
use Softgeng\UploadPost\Exceptions\UploadPostException;
use Softgeng\UploadPost\Exceptions\UploadPostValidationException;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Throwable;

final readonly class UploadPostClient
{
    private HttpFactory $httpFactory;

    public function __construct(
        private UploadPostConfig $config,
        ?HttpFactory $httpFactory = null,
    ) {
        $this->httpFactory = $httpFactory ?? new HttpFactory;
    }

    public static function make(
        string $api_key,
        ?UploadPostConfig $config = null,
        ?HttpFactory $httpFactory = null
    ): self {
        if (! $config instanceof UploadPostConfig) {
            return new self(new UploadPostConfig(apiKey: $api_key), $httpFactory);
        }

        return new self(new UploadPostConfig(
            apiKey: $api_key,
            baseUrl: $config->baseUrl,
            timeout: $config->timeout,
            connectTimeout: $config->connectTimeout,
            retryTimes: $config->retryTimes,
            retrySleepMs: $config->retrySleepMs,
            throwOnValidation: $config->throwOnValidation,
        ), $httpFactory);
    }

    public function uploadVideo(UploadVideoData $data): UploadResponse
    {
        return UploadResponse::fromArray(
            $this->multipart('/upload', $data->toMultipart()->all(), $this->idempotencyHeaders($data->idempotency_key))
        );
    }

    public function uploadPhotos(UploadPhotosData $data): UploadResponse
    {
        return UploadResponse::fromArray(
            $this->multipart('/upload_photos', $data->toMultipart()->all(), $this->idempotencyHeaders($data->idempotency_key))
        );
    }

    public function uploadText(UploadTextData $data): UploadResponse
    {
        return UploadResponse::fromArray(
            $this->multipart('/upload_text', $data->toMultipart()->all(), $this->idempotencyHeaders($data->idempotency_key))
        );
    }

    public function uploadDocument(UploadDocumentData $data): UploadResponse
    {
        return UploadResponse::fromArray(
            $this->multipart('/upload_document', $data->toMultipart()->all(), $this->idempotencyHeaders($data->idempotency_key))
        );
    }

    public function getStatus(string $request_id): StatusResponse
    {
        return StatusResponse::fromArray($this->get('/uploadposts/status', ['request_id' => $request_id]));
    }

    public function getJobStatus(string $job_id): StatusResponse
    {
        return StatusResponse::fromArray($this->get('/uploadposts/status', ['job_id' => $job_id]));
    }

    public function getHistory(int $page = 1, int $limit = 20): HistoryResponse
    {
        return HistoryResponse::fromArray(
            $this->get('/uploadposts/history', ['page' => $page, 'limit' => $limit])
        );
    }

    public function getAnalytics(string $profileUsername, ?AnalyticsQueryData $query = null): AnalyticsResponse
    {
        return AnalyticsResponse::fromArray(
            $this->get('/analytics/'.rawurlencode($profileUsername), $query?->toQuery() ?? [])
        );
    }

    /**
     * @param  array<string,mixed>  $query
     */
    public function getTotalImpressions(string $profileUsername, array $query = []): GenericResponse
    {
        return GenericResponse::fromArray(
            $this->get('/uploadposts/total-impressions/'.rawurlencode($profileUsername), $query)
        );
    }

    public function getPostAnalytics(string $request_id): GenericResponse
    {
        return GenericResponse::fromArray(
            $this->get('/uploadposts/post-analytics/'.rawurlencode($request_id))
        );
    }

    public function getPostAnalyticsByPlatformId(
        string $platform_post_id,
        string $platform,
        string $user
    ): GenericResponse {
        return GenericResponse::fromArray(
            $this->get(
                '/uploadposts/post-analytics',
                ['platform_post_id' => $platform_post_id, 'platform' => $platform, 'user' => $user]
            )
        );
    }

    public function getPlatformMetrics(): GenericResponse
    {
        return GenericResponse::fromArray($this->get('/uploadposts/platform-metrics'));
    }

    /**
     * @param  array<string,string>  $query
     */
    public function getMedia(string $platform, string $user, array $query = []): MediaResponse
    {
        return MediaResponse::fromArray(
            $this->get('/uploadposts/media', ['platform' => $platform, 'user' => $user, ...$query])
        );
    }

    public function listScheduled(): ScheduledPostsResponse
    {
        return ScheduledPostsResponse::fromArray($this->get('/uploadposts/schedule'));
    }

    public function cancelScheduled(string $job_id): ActionResponse
    {
        return ActionResponse::fromArray($this->delete('/uploadposts/schedule/'.rawurlencode($job_id)));
    }

    public function editScheduled(
        string $job_id,
        string $scheduled_date,
        ?string $timezone = null
    ): ScheduledPostResponse {
        return ScheduledPostResponse::fromArray(
            $this->patch(
                '/uploadposts/schedule/'.rawurlencode($job_id),
                $this->clean(['scheduled_date' => $scheduled_date, 'timezone' => $timezone])
            )
        );
    }

    public function getQueueSettings(string $profileUsername): QueueSettingsResponse
    {
        return QueueSettingsResponse::fromArray(
            $this->get('/uploadposts/queue/settings', ['profile_username' => $profileUsername])
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateQueueSettings(string $profileUsername, array $settings = []): QueueSettingsResponse
    {
        return QueueSettingsResponse::fromArray(
            $this->post(
                '/uploadposts/queue/settings',
                $this->clean(['profile_username' => $profileUsername, ...$settings])
            )
        );
    }

    public function getQueuePreview(string $profileUsername, ?int $count = null): QueuePreviewResponse
    {
        return QueuePreviewResponse::fromArray(
            $this->get(
                '/uploadposts/queue/preview',
                $this->clean(['profile_username' => $profileUsername, 'count' => $count])
            )
        );
    }

    public function markQueueSlotFull(string $profileUsername, string $slotDatetime): QueueSlotFullResponse
    {
        return QueueSlotFullResponse::fromArray(
            $this->post('/uploadposts/queue/slot-full', [
                'profile_username' => $profileUsername,
                'slot_datetime' => $slotDatetime,
            ])
        );
    }

    public function unmarkQueueSlotFull(string $profileUsername, string $slotDatetime): QueueSlotFullResponse
    {
        return QueueSlotFullResponse::fromArray(
            $this->delete('/uploadposts/queue/slot-full', [
                'profile_username' => $profileUsername,
                'slot_datetime' => $slotDatetime,
            ])
        );
    }

    public function getNextAvailableSlot(string $profileUsername): QueueNextSlotResponse
    {
        return QueueNextSlotResponse::fromArray(
            $this->get('/uploadposts/queue/next-slot', ['profile_username' => $profileUsername])
        );
    }

    public function listUsers(): UserProfilesResponse
    {
        return UserProfilesResponse::fromArray($this->get('/uploadposts/users'));
    }

    public function getUser(string $username): UserResponse
    {
        return UserResponse::fromArray($this->get('/uploadposts/users/'.rawurlencode($username)));
    }

    public function createUser(string $username): UserResponse
    {
        return UserResponse::fromArray($this->post('/uploadposts/users', ['username' => $username]));
    }

    public function deleteUser(string $username): ActionResponse
    {
        return ActionResponse::fromArray($this->delete('/uploadposts/users', ['username' => $username]));
    }

    public function generateJwt(GenerateJwtData $data): JwtResponse
    {
        return JwtResponse::fromArray($this->post('/uploadposts/users/generate-jwt', $data->toArray()));
    }

    public function validateJwt(string $jwt): ActionResponse
    {
        return ActionResponse::fromArray($this->post('/uploadposts/users/validate-jwt', ['jwt' => $jwt]));
    }

    public function getUserPreferences(): GenericResponse
    {
        return GenericResponse::fromArray($this->get('/uploadposts/users/preferences'));
    }

    /**
     * @param  array<string,mixed>  $preferences
     */
    public function updateUserPreferences(array $preferences): GenericResponse
    {
        return GenericResponse::fromArray($this->post('/uploadposts/users/preferences', $preferences));
    }

    public function getNotificationConfig(): GenericResponse
    {
        return GenericResponse::fromArray($this->get('/uploadposts/notification-config'));
    }

    /**
     * @param  array<string,mixed>  $config
     */
    public function updateNotificationConfig(array $config): GenericResponse
    {
        return GenericResponse::fromArray($this->post('/uploadposts/notification-config', $config));
    }

    public function configureNotifications(NotificationConfigData $data): NotificationConfigResponse
    {
        return NotificationConfigResponse::fromArray(
            $this->post('/uploadposts/users/notifications', $data->toArray())
        );
    }

    /**
     * @param  array<string, mixed>  $webhook_events
     */
    public function configureWebhook(
        string $webhook_url,
        array $webhook_events = []
    ): NotificationConfigResponse {
        return $this->configureNotifications(NotificationConfigData::webhook($webhook_url, $webhook_events));
    }

    /**
     * @param  array<string,string>  $query
     */
    public function getPostComments(string $user, array $query = []): CommentsResponse
    {
        return CommentsResponse::fromArray(
            $this->get('/uploadposts/comments', ['platform' => 'instagram', 'user' => $user, ...$query])
        );
    }

    public function replyToComment(string $user, string $commentId, string $message): ActionResponse
    {
        return ActionResponse::fromArray(
            $this->post(
                '/uploadposts/comments/reply',
                [
                    'platform' => 'instagram',
                    'user' => $user,
                    'comment_id' => $commentId,
                    'message' => $message,
                ]
            )
        );
    }

    public function publicReplyToComment(string $user, string $commentId, string $message): ActionResponse
    {
        return ActionResponse::fromArray(
            $this->post(
                '/uploadposts/comments/public-reply',
                [
                    'platform' => 'instagram',
                    'user' => $user,
                    'comment_id' => $commentId,
                    'message' => $message,
                ]
            )
        );
    }

    public function getFacebookPages(?string $profile = null): FacebookPagesResponse
    {
        return FacebookPagesResponse::fromArray(
            $this->get('/uploadposts/facebook/pages', $this->clean(['profile' => $profile]))
        );
    }

    public function getLinkedinPages(?string $profile = null): LinkedinPagesResponse
    {
        return LinkedinPagesResponse::fromArray(
            $this->get('/uploadposts/linkedin/pages', $this->clean(['profile' => $profile]))
        );
    }

    public function getPinterestBoards(?string $profile = null): PinterestBoardsResponse
    {
        return PinterestBoardsResponse::fromArray(
            $this->get('/uploadposts/pinterest/boards', $this->clean(['profile' => $profile]))
        );
    }

    public function getGoogleBusinessLocations(?string $profile = null): GoogleBusinessLocationsResponse
    {
        return GoogleBusinessLocationsResponse::fromArray(
            $this->get('/uploadposts/google-business/locations', $this->clean(['profile' => $profile]))
        );
    }

    public function selectGoogleBusinessLocation(string $locationId, ?string $profile = null): ActionResponse
    {
        return ActionResponse::fromArray(
            $this->post(
                '/uploadposts/google-business/locations/select',
                $this->clean(['location_id' => $locationId, 'profile' => $profile])
            )
        );
    }

    private function http(): PendingRequest
    {
        return $this->httpFactory
            ->baseUrl($this->config->baseUrl)
            ->acceptJson()
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout)
            ->retry($this->config->retryTimes, $this->config->retrySleepMs, throw: false)
            ->withHeaders([
                'Authorization' => 'Apikey '.$this->config->apiKey,
            ]);
    }

    /**
     * @param  list<array{
     *  name:string,
     *  contents:mixed,
     *  filename?:string,
     *  headers?:array<string, string>
     * }> $parts
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function multipart(string $endpoint, array $parts, array $headers = []): array
    {
        return $this->send(
            fn () => $this->http()
                ->withHeaders($headers)
                ->send('POST', $endpoint, ['multipart' => $parts])
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query = []): array
    {
        return $this->send(fn () => $this->http()->get($endpoint, $query));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $body = []): array
    {
        return $this->send(fn () => $this->http()->asJson()->post($endpoint, $body));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function patch(string $endpoint, array $body = []): array
    {
        return $this->send(fn () => $this->http()->asJson()->patch($endpoint, $body));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function delete(string $endpoint, array $body = []): array
    {
        return $this->send(fn () => $this->http()->asJson()->delete($endpoint, $body));
    }

    /** @return array<string, mixed> */
    private function send(callable $request): array
    {
        try {
            /** @var Response $response */
            $response = $request();
        } catch (ConnectionException $e) {
            throw new UploadPostConnectionException(
                'Could not connect to Upload-Post API: '.$e->getMessage(),
                previous: $e
            );
        } catch (Throwable $e) {
            throw new UploadPostConnectionException(
                'Upload-Post request failed: '.$e->getMessage(),
                previous: $e
            );
        }

        if ($response->failed()) {
            if ($response->status() === 422 && $this->config->throwOnValidation) {
                throw UploadPostValidationException::fromResponse($response);
            }

            throw UploadPostException::fromResponse($response);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        return array_filter($data, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    private function idempotencyHeaders(?string $idempotencyKey): array
    {
        if ($idempotencyKey === null || trim($idempotencyKey) === '') {
            return [];
        }

        return ['X-Idempotency-Key' => $idempotencyKey];
    }
}
