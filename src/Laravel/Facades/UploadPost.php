<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
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
use Softgeng\UploadPost\Data\Responses\TikTokLocationsResponse;
use Softgeng\UploadPost\Data\Responses\TikTokMusicResponse;
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
use Softgeng\UploadPost\Support\UploadPostConfig;
use Softgeng\UploadPost\Testing\UploadPostFake;
use Softgeng\UploadPost\UploadPostClient;

/**
 * @method static UploadResponse uploadVideo(UploadVideoData $data)
 * @method static UploadResponse uploadPhotos(UploadPhotosData $data)
 * @method static UploadResponse uploadText(UploadTextData $data)
 * @method static UploadResponse uploadDocument(UploadDocumentData $data)
 * @method static StatusResponse getStatus(string $request_id)
 * @method static StatusResponse getJobStatus(string $job_id)
 * @method static HistoryResponse getHistory(int|HistoryQueryData $page = 1, int $limit = 10)
 * @method static AnalyticsResponse getAnalytics(string $profileUsername, ?AnalyticsQueryData $query = null)
 * @method static TotalImpressionsResponse getTotalImpressions(string $profileUsername, array<string, mixed> $query = [])
 * @method static PostAnalyticsResponse getPostAnalytics(string $request_id, ?string $platform = null)
 * @method static PostAnalyticsResponse getPostAnalyticsByPlatformId(string $platform_post_id, string $platform, string $user)
 * @method static PlatformMetricsResponse getPlatformMetrics()
 * @method static MediaResponse getMedia(string $platform, string $user, array<string, string> $query = [])
 * @method static ScheduledPostsResponse listScheduled(?ScheduledPostsQueryData $query = null)
 * @method static ActionResponse cancelScheduled(string $job_id)
 * @method static ScheduledPostResponse editScheduled(string $job_id, ?string $scheduled_date = null, ?string $timezone = null, ?string $title = null, ?string $caption = null)
 * @method static QueueSettingsResponse getQueueSettings(string $profileUsername)
 * @method static QueueSettingsResponse updateQueueSettings(string $profileUsername, array<string, mixed> $settings = [])
 * @method static QueuePreviewResponse getQueuePreview(string $profileUsername, ?int $count = null)
 * @method static QueueSlotFullResponse markQueueSlotFull(string $profileUsername, string $slotDatetime)
 * @method static QueueSlotFullResponse unmarkQueueSlotFull(string $profileUsername, string $slotDatetime)
 * @method static QueueNextSlotResponse getNextAvailableSlot(string $profileUsername)
 * @method static UserProfilesResponse listUsers()
 * @method static UserResponse createUser(string $username)
 * @method static UserResponse getUser(string $username)
 * @method static ActionResponse deleteUser(string $username)
 * @method static JwtResponse generateJwt(GenerateJwtData $data)
 * @method static UserResponse validateJwt(string $jwt)
 * @method static UserPreferencesResponse getUserPreferences()
 * @method static UserPreferencesResponse updateUserPreferences(array<string, mixed> $preferences)
 * @method static NotificationConfigResponse getNotificationConfig()
 * @method static NotificationConfigResponse updateNotificationConfig(array<string, mixed> $config)
 * @method static NotificationConfigResponse deleteNotificationConfig()
 * @method static NotificationConfigResponse configureNotifications(NotificationConfigData $data)
 * @method static NotificationConfigResponse configureWebhook(string $webhook_url, array<string, mixed> $webhook_events = [])
 * @method static CommentsResponse getPostComments(CommentQueryData $data)
 * @method static ActionResponse createComment(CreateCommentData $data)
 * @method static ActionResponse deleteComment(DeleteCommentData $data)
 * @method static ActionResponse actOnComment(CommentActionData $data)
 * @method static FacebookPagesResponse getFacebookPages(?string $profile = null)
 * @method static FacebookPagesResponse getFacebookPage(string $profileUsername)
 * @method static ActionResponse selectFacebookPage(string $pageId, string $profileUsername)
 * @method static ActionResponse clearFacebookPage(string $profileUsername)
 * @method static LinkedinPagesResponse getLinkedinPages(?string $profile = null)
 * @method static LinkedinPagesResponse getLinkedinPage(string $profileUsername)
 * @method static ActionResponse selectLinkedinPage(string $pageId, string $profileUsername)
 * @method static ActionResponse clearLinkedinPage(string $profileUsername)
 * @method static PinterestBoardsResponse getPinterestBoards(?string $profile = null)
 * @method static GoogleBusinessLocationsResponse getGoogleBusinessLocations(?string $profile = null)
 * @method static GoogleBusinessLocationsResponse getGoogleBusinessLocation(string $profileUsername)
 * @method static ActionResponse selectGoogleBusinessLocation(string $locationId, string $profileUsername)
 * @method static ActionResponse clearGoogleBusinessLocation(string $profileUsername)
 * @method static TikTokMusicResponse getTikTokTrendingMusic(string $profile, ?string $genre = null, ?string $countryCode = null, ?string $dateRange = null)
 * @method static TikTokMusicResponse searchTikTokMusic(string $profile, ?string $q = null, ?string $genre = null, ?string $countryCode = null, ?string $dateRange = null, ?int $limit = null)
 * @method static TikTokLocationsResponse getTikTokLocations(string $profile, string $q)
 */
final class UploadPost extends Facade
{
    /**
     * @param  array<string, mixed>|callable  $responses
     */
    public static function fake(array|callable $responses = []): UploadPostFake
    {
        $fake = UploadPostFake::make(
            $responses,
            UploadPostConfig::fromArray(config('upload-post')),
        );

        app()->instance(UploadPostFake::class, $fake);
        app()->instance(UploadPostClient::class, $fake->client());

        self::swap($fake->client());

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return UploadPostClient::class;
    }
}
