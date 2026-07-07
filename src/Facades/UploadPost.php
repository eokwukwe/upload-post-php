<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Facades;

use Illuminate\Support\Facades\Facade;
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
use Softgeng\UploadPost\UploadPostClient;

/**
 * @method static UploadResponse uploadVideo(UploadVideoData $data)
 * @method static UploadResponse uploadPhotos(UploadPhotosData $data)
 * @method static UploadResponse uploadText(UploadTextData $data)
 * @method static UploadResponse uploadDocument(UploadDocumentData $data)
 * @method static StatusResponse getStatus(string $request_id)
 * @method static StatusResponse getJobStatus(string $job_id)
 * @method static HistoryResponse getHistory(int $page = 1, int $limit = 20)
 * @method static AnalyticsResponse getAnalytics(string $profileUsername, ?AnalyticsQueryData $query = null)
 * @method static GenericResponse getTotalImpressions(string $profileUsername, array<string, mixed> $query = [])
 * @method static GenericResponse getPostAnalytics(string $request_id)
 * @method static GenericResponse getPostAnalyticsByPlatformId(string $platform_post_id, string $platform, string $user)
 * @method static GenericResponse getPlatformMetrics()
 * @method static MediaResponse getMedia(string $platform, string $user, array<string, string> $query = [])
 * @method static ScheduledPostsResponse listScheduled()
 * @method static ActionResponse cancelScheduled(string $job_id)
 * @method static ScheduledPostResponse editScheduled(string $job_id, string $scheduled_date, ?string $timezone = null)
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
 * @method static ActionResponse validateJwt(string $jwt)
 * @method static GenericResponse getUserPreferences()
 * @method static GenericResponse updateUserPreferences(array<string, mixed> $preferences)
 * @method static GenericResponse getNotificationConfig()
 * @method static GenericResponse updateNotificationConfig(array<string, mixed> $config)
 * @method static NotificationConfigResponse configureNotifications(NotificationConfigData $data)
 * @method static NotificationConfigResponse configureWebhook(string $webhook_url, array<string, mixed> $webhook_events = [])
 * @method static CommentsResponse getPostComments(string $user, array<string, string> $query = [])
 * @method static ActionResponse replyToComment(string $user, string $commentId, string $message)
 * @method static ActionResponse publicReplyToComment(string $user, string $commentId, string $message)
 * @method static FacebookPagesResponse getFacebookPages(?string $profile = null)
 * @method static LinkedinPagesResponse getLinkedinPages(?string $profile = null)
 * @method static PinterestBoardsResponse getPinterestBoards(?string $profile = null)
 * @method static GoogleBusinessLocationsResponse getGoogleBusinessLocations(?string $profile = null)
 * @method static ActionResponse selectGoogleBusinessLocation(string $locationId, ?string $profile = null)
 */
final class UploadPost extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UploadPostClient::class;
    }
}
