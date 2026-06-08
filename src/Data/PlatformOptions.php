<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use Softgeng\UploadPost\Enums\FacebookMediaType;
use Softgeng\UploadPost\Enums\FacebookVideoState;
use Softgeng\UploadPost\Enums\InstagramMediaType;
use Softgeng\UploadPost\Enums\LinkedinVisibility;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Enums\TiktokPostMode;
use Softgeng\UploadPost\Enums\TiktokPrivacyLevel;
use Softgeng\UploadPost\Enums\XReplySettings;
use Softgeng\UploadPost\Enums\YoutubePrivacyStatus;
use Softgeng\UploadPost\Support\Media;
use Softgeng\UploadPost\Support\MultipartPayload;

final readonly class PlatformOptions
{
    use Concerns;

    /**
     * @param  list<string>  $youtube_tags
     * @param  list<YoutubeSubtitleData>  $youtube_subtitles
     * @param  list<string>  $x_tagged_user_ids
     * @param  list<string>  $x_poll_options
     */
    public function __construct(
        public ?bool $tiktok_disable_comment = null,
        public ?bool $brand_content_toggle = null,
        public ?bool $brand_organic_toggle = null,
        public TiktokPrivacyLevel|string|null $tiktok_privacy_level = null,
        public ?bool $tiktok_disable_duet = null,
        public ?bool $tiktok_disable_stitch = null,
        public int|string|null $tiktok_cover_timestamp = null,
        public ?bool $tiktok_is_aigc = null,
        public TiktokPostMode|string|null $tiktok_post_mode = null,
        public ?bool $tiktok_auto_add_music = null,
        public ?int $tiktok_photo_cover_index = null,

        public InstagramMediaType|string|null $instagram_media_type = null,
        public ?string $instagram_collaborators = null,
        public ?string $instagram_user_tags = null,
        public ?string $instagram_location_id = null,
        public ?bool $instagram_share_to_feed = null,
        public Media|string|null $instagram_cover = null,
        public ?string $instagram_audio_name = null,
        public ?string $instagram_thumb_offset = null,

        public array $youtube_tags = [],
        public ?string $youtube_category_id = null,
        public YoutubePrivacyStatus|string|null $youtube_privacy_status = null,
        public ?bool $youtube_embeddable = null,
        public ?string $youtube_license = null,
        public ?bool $youtube_public_stats_viewable = null,
        public ?string $youtube_thumbnail_url = null,
        public ?bool $youtube_self_declared_made_for_kids = null,
        public ?bool $youtube_contains_synthetic_media = null,
        public ?string $youtube_default_language = null,
        public ?string $youtube_default_audio_language = null,
        public ?string $youtube_allowed_countries = null,
        public ?string $youtube_blocked_countries = null,
        public ?bool $youtube_has_paid_product_placement = null,
        public ?string $youtube_recording_date = null,
        public array $youtube_subtitles = [],

        public LinkedinVisibility|string|null $linkedin_visibility = null,
        public ?string $target_linkedin_page_id = null,
        public ?string $linkedin_link_url = null,

        public ?string $facebook_page_id = null,
        public FacebookVideoState|string|null $facebook_video_state = null,
        public FacebookMediaType|string|null $facebook_media_type = null,
        public ?string $thumbnail_url = null,
        public ?string $facebook_link_url = null,

        public ?string $pinterest_board_id = null,
        public ?string $pinterest_alt_text = null,
        public ?string $pinterest_link = null,
        public ?string $pinterest_cover_image_url = null,
        public ?string $pinterest_cover_image_content_type = null,
        public ?string $pinterest_cover_image_data = null,
        public int|string|null $pinterest_cover_image_key_frame_time = null,

        public XReplySettings|string|null $x_reply_settings = null,
        public ?bool $x_nullcast = null,
        public ?string $x_quote_tweet_id = null,
        public ?string $x_geo_place_id = null,
        public ?bool $x_for_super_followers_only = null,
        public ?string $x_community_id = null,
        public ?bool $x_share_with_followers = null,
        public ?string $x_direct_message_deep_link = null,
        public ?bool $x_long_text_as_post = null,
        public array $x_tagged_user_ids = [],
        public ?string $x_place_id = null,
        public ?string $x_thread_image_layout = null,
        public ?string $x_post_url = null,
        public ?string $x_card_uri = null,
        public array $x_poll_options = [],
        public int|string|null $x_poll_duration = null,
        public XReplySettings|string|null $x_poll_reply_settings = null,

        public ?bool $threads_long_text_as_post = null,
        public ?string $threads_thread_media_layout = null,
        public ?string $threads_topic_tag = null,

        public ?string $reddit_subreddit = null,
        public ?string $reddit_flair_id = null,
        public ?string $reddit_link_url = null,

        public ?string $bluesky_link_url = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /** 
     * @param list<Platform|string> $platforms 
     */
    public function addForVideo(MultipartPayload $payload, array $platforms): MultipartPayload
    {
        if ($this->hasPlatform($platforms, Platform::TikTok)) {
            $this->addTiktok($payload, true);
        }
        if ($this->hasPlatform($platforms, Platform::Instagram)) {
            $this->addInstagram($payload, true);
        }
        if ($this->hasPlatform($platforms, Platform::YouTube)) {
            $this->addYoutube($payload);
        }
        if ($this->hasPlatform($platforms, Platform::LinkedIn)) {
            $this->addLinkedin($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->addFacebook($payload, true, false);
        }
        if ($this->hasPlatform($platforms, Platform::Pinterest)) {
            $this->addPinterest($payload, true);
        }
        if ($this->hasPlatform($platforms, Platform::X)) {
            $this->addX($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload);
        }

        return $payload;
    }

    /** 
     * @param list<Platform|string> $platforms 
     */
    public function addForPhotos(MultipartPayload $payload, array $platforms): MultipartPayload
    {
        if ($this->hasPlatform($platforms, Platform::TikTok)) {
            $this->addTiktok($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::Instagram)) {
            $this->addInstagram($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::LinkedIn)) {
            $this->addLinkedin($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->addFacebook($payload, false, false);
        }
        if ($this->hasPlatform($platforms, Platform::Pinterest)) {
            $this->addPinterest($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::X)) {
            $this->addX($payload, false);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload);
        }
        if ($this->hasPlatform($platforms, Platform::Reddit)) {
            $this->addReddit($payload, false);
        }

        return $payload;
    }

    /** 
     * @param list<Platform|string> $platforms 
     */
    public function addForText(
        MultipartPayload $payload,
        array $platforms,
        ?string $link_url = null
    ): MultipartPayload {
        if ($this->hasPlatform($platforms, Platform::LinkedIn)) {
            $this->addLinkedin($payload, true, $link_url);
        }
        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->addFacebook($payload, false, true);
        }
        if ($this->hasPlatform($platforms, Platform::X)) {
            $this->addX($payload, true);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload);
        }
        if ($this->hasPlatform($platforms, Platform::Reddit)) {
            $this->addReddit($payload, true, $link_url);
        }
        if ($this->hasPlatform($platforms, Platform::Bluesky)) {
            $payload->field('bluesky_link_url', $this->bluesky_link_url ?? $link_url);
        }

        return $payload;
    }

    public function addForDocument(MultipartPayload $payload): MultipartPayload
    {
        return $this->addLinkedin($payload, false);
    }

    private function addTiktok(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('disable_comment', $this->tiktok_disable_comment)
            ->field('brand_content_toggle', $this->brand_content_toggle)
            ->field('brand_organic_toggle', $this->brand_organic_toggle);
        if ($is_video) {
            $p->field('privacy_level', self::enumValue($this->tiktok_privacy_level))
                ->field('disable_duet', $this->tiktok_disable_duet)
                ->field('disable_stitch', $this->tiktok_disable_stitch)
                ->field('cover_timestamp', $this->tiktok_cover_timestamp)
                ->field('is_aigc', $this->tiktok_is_aigc)
                ->field('post_mode', self::enumValue($this->tiktok_post_mode));
        } else {
            $p->field('auto_add_music', $this->tiktok_auto_add_music)
                ->field('photo_cover_index', $this->tiktok_photo_cover_index);
        }

        return $p;
    }

    private function addInstagram(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('media_type', self::enumValue($this->instagram_media_type))
            ->field('collaborators', $this->instagram_collaborators)
            ->field('user_tags', $this->instagram_user_tags)
            ->field('location_id', $this->instagram_location_id);
        if ($is_video) {
            $p->field('share_to_feed', $this->instagram_share_to_feed)
                ->field('audio_name', $this->instagram_audio_name)
                ->field('thumb_offset', $this->instagram_thumb_offset);
            if ($this->instagram_cover !== null) {
                $cover = $this->instagram_cover instanceof Media ? $this->instagram_cover : Media::from($this->instagram_cover);
                $p->media($cover->isUrl() ? 'cover_url' : 'cover_image', $cover);
            }
        }

        return $p;
    }

    private function addYoutube(MultipartPayload $p): MultipartPayload
    {
        $p->field('tags[]', $this->youtube_tags)
            ->field('categoryId', $this->youtube_category_id)
            ->field('privacyStatus', self::enumValue($this->youtube_privacy_status))
            ->field('embeddable', $this->youtube_embeddable)
            ->field('license', $this->youtube_license)
            ->field('publicStatsViewable', $this->youtube_public_stats_viewable)
            ->field('thumbnail_url', $this->youtube_thumbnail_url)
            ->field('selfDeclaredMadeForKids', $this->youtube_self_declared_made_for_kids)
            ->field('containsSyntheticMedia', $this->youtube_contains_synthetic_media)
            ->field('defaultLanguage', $this->youtube_default_language)
            ->field('defaultAudioLanguage', $this->youtube_default_audio_language)
            ->field('allowedCountries', $this->youtube_allowed_countries)
            ->field('blockedCountries', $this->youtube_blocked_countries)
            ->field('hasPaidProductPlacement', $this->youtube_has_paid_product_placement)
            ->field('recordingDate', $this->youtube_recording_date);
        foreach ($this->youtube_subtitles as $idx => $subtitle) {
            $subtitle->addTo($p, (int) $idx);
        }

        return $p;
    }

    private function addLinkedin(
        MultipartPayload $p,
        bool $is_text,
        ?string $link_url = null
    ): MultipartPayload {
        $p->field('visibility', self::enumValue($this->linkedin_visibility))
            ->field('target_linkedin_page_id', $this->target_linkedin_page_id);
        if ($is_text) {
            $p->field('linkedin_link_url', $this->linkedin_link_url ?? $link_url);
        }

        return $p;
    }

    private function addFacebook(MultipartPayload $p, bool $is_video, bool $is_text): MultipartPayload
    {
        $p->field('facebook_page_id', $this->facebook_page_id);
        if ($is_video) {
            $p->field('video_state', self::enumValue($this->facebook_video_state))
                ->field('facebook_media_type', self::enumValue($this->facebook_media_type))
                ->field('thumbnail_url', $this->thumbnail_url);
        }
        if ($is_text) {
            $p->field('facebook_link_url', $this->facebook_link_url);
        }

        return $p;
    }

    private function addPinterest(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('pinterest_board_id', $this->pinterest_board_id)
            ->field('pinterest_alt_text', $this->pinterest_alt_text)
            ->field('pinterest_link', $this->pinterest_link);
        if ($is_video) {
            $p->field('pinterest_cover_image_url', $this->pinterest_cover_image_url)
                ->field('pinterest_cover_image_content_type', $this->pinterest_cover_image_content_type)
                ->field('pinterest_cover_image_data', $this->pinterest_cover_image_data)
                ->field('pinterest_cover_image_key_frame_time', $this->pinterest_cover_image_key_frame_time);
        }

        return $p;
    }

    private function addX(MultipartPayload $p, bool $is_text): MultipartPayload
    {
        $reply = self::enumValue($this->x_reply_settings);
        if ($reply === 'everyone') {
            $reply = null;
        }
        $p->field('reply_settings', $reply)
            ->field('nullcast', $this->x_nullcast)
            ->field('quote_tweet_id', $this->x_quote_tweet_id)
            ->field('geo_place_id', $this->x_geo_place_id)
            ->field('for_super_followers_only', $this->x_for_super_followers_only)
            ->field('community_id', $this->x_community_id)
            ->field('share_with_followers', $this->x_share_with_followers)
            ->field('direct_message_deep_link', $this->x_direct_message_deep_link)
            ->field('x_long_text_as_post', $this->x_long_text_as_post);
        if ($is_text) {
            $p->field('post_url', $this->x_post_url)
                ->field('card_uri', $this->x_card_uri)
                ->field('poll_options[]', $this->x_poll_options)
                ->field('poll_duration', $this->x_poll_duration)
                ->field('poll_reply_settings', self::enumValue($this->x_poll_reply_settings));
        } else {
            $p->field('tagged_user_ids[]', $this->x_tagged_user_ids)
                ->field('place_id', $this->x_place_id)
                ->field('x_thread_image_layout', $this->x_thread_image_layout);
        }

        return $p;
    }

    private function addThreads(MultipartPayload $p): MultipartPayload
    {
        return $p->field('threads_long_text_as_post', $this->threads_long_text_as_post)
            ->field('threads_thread_media_layout', $this->threads_thread_media_layout)
            ->field('threads_topic_tag', $this->threads_topic_tag);
    }

    private function addReddit(MultipartPayload $p, bool $is_text, ?string $link_url = null): MultipartPayload
    {
        $p->field('subreddit', $this->reddit_subreddit)->field('flair_id', $this->reddit_flair_id);
        if ($is_text) {
            $p->field('reddit_link_url', $this->reddit_link_url ?? $link_url);
        }

        return $p;
    }

    /** 
     * @param list<Platform|string> $platforms
     */
    private function hasPlatform(array $platforms, Platform $platform): bool
    {
        return in_array($platform->value, self::platformsToValues($platforms), true);
    }
}
