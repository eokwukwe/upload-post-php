<?php

declare(strict_types=1);

namespace Softgeng\UploadPost;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\AnalyticsQueryData;
use Softgeng\UploadPost\Data\CommentActionData;
use Softgeng\UploadPost\Data\CommentQueryData;
use Softgeng\UploadPost\Data\CreateCommentData;
use Softgeng\UploadPost\Data\DeleteCommentData;
use Softgeng\UploadPost\Data\GenerateJwtData;
use Softgeng\UploadPost\Data\HistoryQueryData;
use Softgeng\UploadPost\Data\NotificationConfigData;
use Softgeng\UploadPost\Data\Responses\ActionResponse;
use Softgeng\UploadPost\Data\Responses\AnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\CommentsResponse;
use Softgeng\UploadPost\Data\Responses\FacebookPagesResponse;
use Softgeng\UploadPost\Data\Responses\GoogleBusinessLocationsResponse;
use Softgeng\UploadPost\Data\Responses\HistoryResponse;
use Softgeng\UploadPost\Data\Responses\JwtResponse;
use Softgeng\UploadPost\Data\Responses\LinkedinPagesResponse;
use Softgeng\UploadPost\Data\Responses\MediaResponse;
use Softgeng\UploadPost\Data\Responses\NotificationConfigResponse;
use Softgeng\UploadPost\Data\Responses\PinterestBoardsResponse;
use Softgeng\UploadPost\Data\Responses\PlatformMetricsResponse;
use Softgeng\UploadPost\Data\Responses\PostAnalyticsResponse;
use Softgeng\UploadPost\Data\Responses\QueueNextSlotResponse;
use Softgeng\UploadPost\Data\Responses\QueuePreviewResponse;
use Softgeng\UploadPost\Data\Responses\QueueSettingsResponse;
use Softgeng\UploadPost\Data\Responses\QueueSlotFullResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostResponse;
use Softgeng\UploadPost\Data\Responses\ScheduledPostsResponse;
use Softgeng\UploadPost\Data\Responses\StatusResponse;
use Softgeng\UploadPost\Data\Responses\TotalImpressionsResponse;
use Softgeng\UploadPost\Data\Responses\UploadResponse;
use Softgeng\UploadPost\Data\Responses\UserPreferencesResponse;
use Softgeng\UploadPost\Data\Responses\UserProfilesResponse;
use Softgeng\UploadPost\Data\Responses\UserResponse;
use Softgeng\UploadPost\Data\ScheduledPostsQueryData;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Exceptions\UploadPostConnectionException;
use Softgeng\UploadPost\Exceptions\UploadPostException;
use Softgeng\UploadPost\Exceptions\UploadPostValidationException;
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\Testing\UploadPostFake;
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

    /**
     * @param  array<string, mixed>|callable  $responses
     */
    public static function fake(
        array|callable $responses = [],
        ?UploadPostConfig $config = null
    ): UploadPostFake {
        return UploadPostFake::make($responses, $config);
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
            $this->multipart('/upload_document', $data->toMultipart()->all())
        );
    }

    public function getStatus(string $request_id): StatusResponse
    {
        $this->requireNonBlank($request_id, 'request_id');

        return StatusResponse::fromArray($this->get('/uploadposts/status', ['request_id' => $request_id]));
    }

    public function getJobStatus(string $job_id): StatusResponse
    {
        $this->requireNonBlank($job_id, 'job_id');

        return StatusResponse::fromArray($this->get('/uploadposts/status', ['job_id' => $job_id]));
    }

    public function getHistory(int|HistoryQueryData $page = 1, int $limit = 10): HistoryResponse
    {
        $query = $page instanceof HistoryQueryData
            ? $page
            : new HistoryQueryData(page: $page, limit: $limit);

        return HistoryResponse::fromArray(
            $this->get('/uploadposts/history', $query->toQuery())
        );
    }

    public function getAnalytics(string $profileUsername, ?AnalyticsQueryData $query = null): AnalyticsResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        $queryParameters = $query?->toQuery() ?? [];

        if (! isset($queryParameters['platforms'])) {
            throw new InvalidArgumentException('At least one analytics platform is required.');
        }

        return AnalyticsResponse::fromArray(
            $this->get('/analytics/'.rawurlencode($profileUsername), $queryParameters)
        );
    }

    /**
     * @param  array<string,mixed>  $query
     */
    public function getTotalImpressions(string $profileUsername, array $query = []): TotalImpressionsResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return TotalImpressionsResponse::fromArray(
            $this->get('/uploadposts/total-impressions/'.rawurlencode($profileUsername), $query)
        );
    }

    public function getPostAnalytics(string $request_id, ?string $platform = null): PostAnalyticsResponse
    {
        $this->requireNonBlank($request_id, 'request_id');

        return PostAnalyticsResponse::fromArray(
            $this->get(
                '/uploadposts/post-analytics/'.rawurlencode($request_id),
                $this->clean(['platform' => $platform]),
            )
        );
    }

    public function getPostAnalyticsByPlatformId(
        string $platform_post_id,
        string $platform,
        string $user
    ): PostAnalyticsResponse {
        $this->requireNonBlank($platform_post_id, 'platform_post_id');
        $this->requireNonBlank($platform, 'platform');
        $this->requireNonBlank($user, 'user');

        return PostAnalyticsResponse::fromArray(
            $this->get(
                '/uploadposts/post-analytics',
                ['platform_post_id' => $platform_post_id, 'platform' => $platform, 'user' => $user]
            )
        );
    }

    public function getPlatformMetrics(): PlatformMetricsResponse
    {
        return PlatformMetricsResponse::fromArray($this->get('/uploadposts/platform-metrics'));
    }

    /**
     * @param  array<string,string>  $query
     */
    public function getMedia(string $platform, string $user, array $query = []): MediaResponse
    {
        $this->requireNonBlank($platform, 'platform');
        $this->requireNonBlank($user, 'user');

        return MediaResponse::fromArray(
            $this->get('/uploadposts/media', [...$query, 'platform' => $platform, 'user' => $user])
        );
    }

    public function listScheduled(?ScheduledPostsQueryData $query = null): ScheduledPostsResponse
    {
        return ScheduledPostsResponse::fromArray(
            $this->get('/uploadposts/schedule', $query?->toQuery() ?? [])
        );
    }

    public function cancelScheduled(string $job_id): ActionResponse
    {
        $this->requireNonBlank($job_id, 'job_id');

        return ActionResponse::fromArray($this->delete('/uploadposts/schedule/'.rawurlencode($job_id)));
    }

    public function editScheduled(
        string $job_id,
        ?string $scheduled_date = null,
        ?string $timezone = null,
        ?string $title = null,
        ?string $caption = null,
    ): ScheduledPostResponse {
        $this->requireNonBlank($job_id, 'job_id');

        if ($scheduled_date === null && $timezone === null && $title === null && $caption === null) {
            throw new InvalidArgumentException('At least one scheduled post field is required.');
        }

        $this->validateScheduledEdit($scheduled_date, $timezone);

        return ScheduledPostResponse::fromArray(
            $this->patch(
                '/uploadposts/schedule/'.rawurlencode($job_id),
                $this->clean([
                    'scheduled_date' => $scheduled_date,
                    'timezone' => $timezone,
                    'title' => $title,
                    'caption' => $caption,
                ])
            )
        );
    }

    public function getQueueSettings(string $profileUsername): QueueSettingsResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return QueueSettingsResponse::fromArray(
            $this->get('/uploadposts/queue/settings', ['profile_username' => $profileUsername])
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateQueueSettings(string $profileUsername, array $settings = []): QueueSettingsResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');
        $this->validateQueueSettings($settings);

        return QueueSettingsResponse::fromArray(
            $this->post(
                '/uploadposts/queue/settings',
                $this->clean([...$settings, 'profile_username' => $profileUsername])
            )
        );
    }

    public function getQueuePreview(string $profileUsername, ?int $count = null): QueuePreviewResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        if ($count !== null && ($count < 1 || $count > 50)) {
            throw new InvalidArgumentException('count must be between 1 and 50.');
        }

        return QueuePreviewResponse::fromArray(
            $this->get(
                '/uploadposts/queue/preview',
                $this->clean(['profile_username' => $profileUsername, 'count' => $count])
            )
        );
    }

    public function markQueueSlotFull(string $profileUsername, string $slotDatetime): QueueSlotFullResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');
        $this->requireNonBlank($slotDatetime, 'slotDatetime');

        return QueueSlotFullResponse::fromArray(
            $this->post('/uploadposts/queue/slot-full', [
                'profile_username' => $profileUsername,
                'slot_datetime' => $slotDatetime,
            ])
        );
    }

    public function unmarkQueueSlotFull(string $profileUsername, string $slotDatetime): QueueSlotFullResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');
        $this->requireNonBlank($slotDatetime, 'slotDatetime');

        return QueueSlotFullResponse::fromArray(
            $this->delete('/uploadposts/queue/slot-full', [
                'profile_username' => $profileUsername,
                'slot_datetime' => $slotDatetime,
            ])
        );
    }

    public function getNextAvailableSlot(string $profileUsername): QueueNextSlotResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

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
        $this->requireNonBlank($username, 'username');

        return UserResponse::fromArray($this->get('/uploadposts/users/'.rawurlencode($username)));
    }

    public function createUser(string $username): UserResponse
    {
        $this->requireNonBlank($username, 'username');

        return UserResponse::fromArray($this->post('/uploadposts/users', ['username' => $username]));
    }

    public function deleteUser(string $username): ActionResponse
    {
        $this->requireNonBlank($username, 'username');

        return ActionResponse::fromArray($this->delete('/uploadposts/users', ['username' => $username]));
    }

    public function generateJwt(GenerateJwtData $data): JwtResponse
    {
        return JwtResponse::fromArray($this->post('/uploadposts/users/generate-jwt', $data->toArray()));
    }

    public function validateJwt(string $jwt): UserResponse
    {
        $this->requireNonBlank($jwt, 'jwt');

        return UserResponse::fromArray(
            $this->send(
                fn () => $this->http('Bearer '.$jwt, withRetry: true)->get('/uploadposts/users/validate-jwt')
            )
        );
    }

    public function getUserPreferences(): UserPreferencesResponse
    {
        return UserPreferencesResponse::fromArray($this->get('/uploadposts/users/preferences'));
    }

    /**
     * @param  array<string,mixed>  $preferences
     */
    public function updateUserPreferences(array $preferences): UserPreferencesResponse
    {
        if (array_key_exists('weekStartDay', $preferences)) {
            $weekStartDay = $preferences['weekStartDay'];

            if (! is_int($weekStartDay) || ! in_array($weekStartDay, [0, 1], true)) {
                throw new InvalidArgumentException('weekStartDay must be 0 (Sunday) or 1 (Monday).');
            }
        }

        return UserPreferencesResponse::fromArray($this->post('/uploadposts/users/preferences', $preferences));
    }

    public function getNotificationConfig(): NotificationConfigResponse
    {
        return NotificationConfigResponse::fromArray($this->get('/uploadposts/users/notifications'));
    }

    /**
     * @param  array<string,mixed>  $config
     */
    public function updateNotificationConfig(array $config): NotificationConfigResponse
    {
        return NotificationConfigResponse::fromArray($this->post('/uploadposts/users/notifications', $config));
    }

    public function deleteNotificationConfig(): NotificationConfigResponse
    {
        return NotificationConfigResponse::fromArray($this->delete('/uploadposts/users/notifications'));
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

    public function getPostComments(CommentQueryData $data): CommentsResponse
    {
        return CommentsResponse::fromArray($this->get('/uploadposts/comments', $data->toQuery()));
    }

    public function createComment(CreateCommentData $data): ActionResponse
    {
        return ActionResponse::fromArray($this->post('/uploadposts/comments/create', $data->toArray()));
    }

    public function deleteComment(DeleteCommentData $data): ActionResponse
    {
        return ActionResponse::fromArray($this->delete('/uploadposts/comments/delete', $data->toArray()));
    }

    public function actOnComment(CommentActionData $data): ActionResponse
    {
        return ActionResponse::fromArray($this->post('/uploadposts/comments/action', $data->toArray()));
    }

    public function getFacebookPages(?string $profile = null): FacebookPagesResponse
    {
        return FacebookPagesResponse::fromArray(
            $this->get('/uploadposts/facebook/pages', $this->clean(['profile' => $profile]))
        );
    }

    public function getFacebookPage(string $profileUsername): FacebookPagesResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return FacebookPagesResponse::fromArray(
            $this->get('/uploadposts/users/facebook-page', $this->clean(['profile_username' => $profileUsername]))
        );
    }

    public function selectFacebookPage(string $pageId, string $profileUsername): ActionResponse
    {
        if (trim($pageId) === '' || trim($profileUsername) === '') {
            throw new InvalidArgumentException('pageId and profileUsername are required.');
        }

        return ActionResponse::fromArray(
            $this->post('/uploadposts/users/facebook-page', $this->clean([
                'profile_username' => $profileUsername,
                'facebook_page_id' => $pageId,
            ]))
        );
    }

    public function clearFacebookPage(string $profileUsername): ActionResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return ActionResponse::fromArray(
            $this->delete('/uploadposts/users/facebook-page', $this->clean(['profile_username' => $profileUsername]))
        );
    }

    public function getLinkedinPages(?string $profile = null): LinkedinPagesResponse
    {
        return LinkedinPagesResponse::fromArray(
            $this->get('/uploadposts/linkedin/pages', $this->clean(['profile' => $profile]))
        );
    }

    public function getLinkedinPage(string $profileUsername): LinkedinPagesResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return LinkedinPagesResponse::fromArray(
            $this->get('/uploadposts/users/linkedin-page', $this->clean(['profile_username' => $profileUsername]))
        );
    }

    public function selectLinkedinPage(string $pageId, string $profileUsername): ActionResponse
    {
        if (trim($pageId) === '' || trim($profileUsername) === '') {
            throw new InvalidArgumentException('pageId and profileUsername are required.');
        }

        return ActionResponse::fromArray(
            $this->post('/uploadposts/users/linkedin-page', $this->clean([
                'profile_username' => $profileUsername,
                'linkedin_page_id' => $pageId,
            ]))
        );
    }

    public function clearLinkedinPage(string $profileUsername): ActionResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return ActionResponse::fromArray(
            $this->delete('/uploadposts/users/linkedin-page', $this->clean(['profile_username' => $profileUsername]))
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

    public function getGoogleBusinessLocation(string $profileUsername): GoogleBusinessLocationsResponse
    {
        $this->requireNonBlank($profileUsername, 'profileUsername');

        return GoogleBusinessLocationsResponse::fromArray(
            $this->get(
                '/uploadposts/users/google-business-location',
                $this->clean(['profile_username' => $profileUsername])
            )
        );
    }

    public function selectGoogleBusinessLocation(string $locationId, string $profileUsername): ActionResponse
    {
        if (trim($locationId) === '' || trim($profileUsername) === '') {
            throw new InvalidArgumentException('locationId and profileUsername are required.');
        }

        return ActionResponse::fromArray(
            $this->post(
                '/uploadposts/users/google-business-location',
                $this->clean([
                    'profile_username' => $profileUsername,
                    'gbp_location_id' => $locationId,
                ])
            )
        );
    }

    public function clearGoogleBusinessLocation(string $profileUsername): ActionResponse
    {
        if (trim($profileUsername) === '') {
            throw new InvalidArgumentException('profileUsername is required.');
        }

        return ActionResponse::fromArray(
            $this->delete(
                '/uploadposts/users/google-business-location',
                $this->clean(['profile_username' => $profileUsername])
            )
        );
    }

    /**
     * Build a request with retries opt-in. Mutating calls must not be replayed
     * unless the endpoint provides an idempotency guarantee.
     */
    private function http(?string $authorization = null, bool $withRetry = false): PendingRequest
    {
        $request = $this->httpFactory
            ->baseUrl($this->config->baseUrl)
            ->acceptJson()
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout);

        if ($withRetry) {
            $request = $request->retry($this->config->retryTimes, $this->config->retrySleepMs, throw: false);
        }

        return $request->withHeaders([
            'Authorization' => $authorization ?? 'Apikey '.$this->config->apiKey,
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
            fn () => $this->http(withRetry: isset($headers['X-Idempotency-Key']))
                ->withHeaders($headers)
                // Uploads are retried only when an idempotency key is present.
                // A connection failure after the server accepted a POST must not
                // create a second post.
                ->send('POST', $endpoint, ['multipart' => $parts])
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query = []): array
    {
        return $this->send(fn () => $this->http(withRetry: true)->get($endpoint, $query));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $body = []): array
    {
        // JSON mutations have no documented idempotency guarantee, so never
        // replay them automatically after an ambiguous response.
        return $this->send(fn () => $this->http(withRetry: false)->asJson()->post($endpoint, $body));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function patch(string $endpoint, array $body = []): array
    {
        return $this->send(fn () => $this->http(withRetry: false)->asJson()->patch($endpoint, $body));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function delete(string $endpoint, array $body = []): array
    {
        return $this->send(fn () => $this->http(withRetry: false)->asJson()->delete($endpoint, $body));
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

    private function requireNonBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$field} is required.");
        }
    }

    private function validateScheduledEdit(?string $scheduled_date, ?string $timezone): void
    {
        if ($timezone !== null && ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('timezone must be a valid IANA timezone.');
        }

        if ($scheduled_date === null) {
            return;
        }

        $scheduledDate = trim($scheduled_date);

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[Tt ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:[Zz]|[+-]\d{2}:?\d{2})?)?$/D', $scheduledDate) !== 1) {
            throw new InvalidArgumentException('scheduled_date must be a valid ISO 8601 date.');
        }

        try {
            $zone = $timezone !== null ? new DateTimeZone($timezone) : new DateTimeZone('UTC');
            $scheduled = new DateTimeImmutable($scheduledDate, $zone);
        } catch (Exception $e) {
            throw new InvalidArgumentException('scheduled_date must be a valid ISO 8601 date.', $e->getCode(), previous: $e);
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($scheduled <= $now) {
            throw new InvalidArgumentException('scheduled_date must be in the future.');
        }

        if ($scheduled > $now->modify('+365 days')) {
            throw new InvalidArgumentException('scheduled_date cannot be more than 365 days in the future.');
        }
    }

    /** @param array<string, mixed> $settings */
    private function validateQueueSettings(array $settings): void
    {
        if (array_key_exists('timezone', $settings)) {
            $timezone = $settings['timezone'];

            if (! is_string($timezone) || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
                throw new InvalidArgumentException('timezone must be a valid IANA timezone.');
            }
        }

        if (array_key_exists('slots', $settings)) {
            if (! is_array($settings['slots']) || count($settings['slots']) > 24) {
                throw new InvalidArgumentException('slots must contain at most 24 entries.');
            }

            foreach ($settings['slots'] as $slot) {
                if (! is_array($slot)) {
                    throw new InvalidArgumentException('Each queue slot must contain an hour from 0 to 23 and a minute from 0 to 59.');
                }

                $hour = $slot['hour'] ?? null;
                $minute = $slot['minute'] ?? null;

                if (! is_int($hour) || ! is_int($minute) || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                    throw new InvalidArgumentException('Each queue slot must contain an hour from 0 to 23 and a minute from 0 to 59.');
                }
            }
        }

        if (array_key_exists('days_of_week', $settings)) {
            if (! is_array($settings['days_of_week'])) {
                throw new InvalidArgumentException('days_of_week must be an array.');
            }

            foreach ($settings['days_of_week'] as $day) {
                if (! is_int($day) || $day < 0 || $day > 6) {
                    throw new InvalidArgumentException('days_of_week values must be between 0 and 6.');
                }
            }
        }

        if (array_key_exists('max_posts_per_slot', $settings)) {
            $maxPostsPerSlot = $settings['max_posts_per_slot'];

            if (! is_int($maxPostsPerSlot) || $maxPostsPerSlot < 1 || $maxPostsPerSlot > 100) {
                throw new InvalidArgumentException('max_posts_per_slot must be between 1 and 100.');
            }
        }
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
