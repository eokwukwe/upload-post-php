<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use Softgeng\UploadPost\Data\Concerns\InteractsWithData;
use Softgeng\UploadPost\Enums\FacebookMediaType;
use Softgeng\UploadPost\Enums\FacebookVideoState;
use Softgeng\UploadPost\Enums\GoogleBusinessCtaType;
use Softgeng\UploadPost\Enums\GoogleBusinessMediaCategory;
use Softgeng\UploadPost\Enums\GoogleBusinessPostType;
use Softgeng\UploadPost\Enums\GoogleBusinessTopicType;
use Softgeng\UploadPost\Enums\InstagramMediaType;
use Softgeng\UploadPost\Enums\LinkedinPollDuration;
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
    use InteractsWithData;

    /**
     * @param  list<string>  $tags
     * @param  list<YoutubeSubtitleData>  $youtube_subtitles
     * @param  list<string>  $tagged_user_ids
     * @param  list<string>  $exclude_reply_user_ids
     * @param  list<string>  $poll_options
     * @param  list<string>  $instagram_alt_text
     * @param  list<string>  $linkedin_alt_text
     * @param  list<string>  $linkedin_poll_options
     * @param  list<string>  $facebook_collaborators
     * @param  list<string>  $facebook_alt_text
     * @param  array<string, mixed>  $facebook_targeting
     * @param  array<string, mixed>  $facebook_feed_targeting
     * @param  array<string, mixed>  $facebook_call_to_action
     * @param  list<array<string, mixed>>  $facebook_child_attachments
     * @param  list<string>  $pinterest_carousel_titles
     * @param  list<string>  $pinterest_carousel_descriptions
     * @param  list<string>  $pinterest_carousel_links
     * @param  list<string>  $x_alt_text
     * @param  list<string>  $threads_alt_text
     * @param  list<string>  $threads_poll_options
     * @param  list<string>  $bluesky_alt_text
     * @param  string|list<string>|null  $pinterest_ai_disclosures
     */
    public function __construct(
        public ?bool $disable_comment = null,
        public ?bool $brand_content_toggle = null,
        public ?bool $brand_organic_toggle = null,
        public TiktokPrivacyLevel|string|null $privacy_level = null,
        public ?bool $disable_duet = null,
        public ?bool $disable_stitch = null,
        public int|string|null $cover_timestamp = null,
        public ?bool $is_aigc = null,
        public TiktokPostMode|string|null $post_mode = null,
        public ?bool $auto_add_music = null,
        public ?int $photo_cover_index = null,
        public ?bool $disable_inbox_fallback = null,
        public string|object|null $tiktok_cover_image = null,
        public ?string $tiktok_cover_image_url = null,
        public ?bool $tiktok_is_ads_only = null,
        public ?string $tiktok_location_id = null,
        public ?string $tiktok_location_name = null,
        public ?string $tiktok_music_id = null,
        public ?int $tiktok_music_start = null,
        public ?int $tiktok_music_end = null,
        public ?int $tiktok_music_volume = null,
        public ?int $tiktok_original_sound_volume = null,
        public ?string $tiktok_tto_invite_link = null,

        public InstagramMediaType|string|null $media_type = null,
        public ?string $collaborators = null,
        public ?string $user_tags = null,
        public ?string $location_id = null,
        public ?string $share_mode = null,
        public ?bool $share_to_feed = null,
        public ?string $cover_url = null,
        public string|object|null $cover_image = null,
        public ?string $audio_name = null,
        public ?string $thumb_offset = null,
        public string|array|null $instagram_alt_text = null,

        public array $tags = [],
        public ?string $categoryId = null,
        public YoutubePrivacyStatus|string|null $privacyStatus = null,
        public ?bool $embeddable = null,
        public ?string $license = null,
        public ?bool $publicStatsViewable = null,
        public string|object|null $thumbnail = null,
        public ?string $thumbnail_url = null,
        public ?bool $selfDeclaredMadeForKids = null,
        public ?bool $containsSyntheticMedia = null,
        public ?string $defaultLanguage = null,
        public ?string $defaultAudioLanguage = null,
        public ?string $allowedCountries = null,
        public ?string $blockedCountries = null,
        public ?bool $hasPaidProductPlacement = null,
        public ?string $recordingDate = null,
        public ?string $youtube_playlist_id = null,
        public array $youtube_subtitles = [],
        public ?bool $youtube_notify_subscribers = null,
        public ?string $youtube_publish_at = null,

        public LinkedinVisibility|string|null $visibility = null,
        public ?string $target_linkedin_page_id = null,
        public ?string $linkedin_link_url = null,
        public ?bool $linkedin_disable_reshare = null,
        public string|array|null $linkedin_alt_text = null,
        public string|object|null $linkedin_subtitles = null,
        public ?string $linkedin_subtitles_url = null,
        public ?string $linkedin_subtitles_text = null,
        public ?string $linkedin_link_title = null,
        public ?string $linkedin_link_description = null,
        public ?string $linkedin_thumbnail_alt_text = null,
        public ?string $linkedin_poll_question = null,
        public array $linkedin_poll_options = [],
        public LinkedinPollDuration|int|string|null $linkedin_poll_duration = null,

        public ?string $facebook_page_id = null,
        public FacebookVideoState|string|null $video_state = null,
        public FacebookMediaType|string|null $facebook_media_type = null,
        public ?string $facebook_link_url = null,
        public string|array|null $facebook_collaborators = null,
        public ?bool $facebook_is_ai_generated = null,
        public ?string $facebook_unpublished_content_type = null,
        public ?bool $facebook_no_story = null,
        public ?bool $facebook_secret = null,
        public string|array|null $facebook_alt_text = null,
        public ?string $facebook_place_id = null,
        public string|array|null $facebook_targeting = null,
        public string|array|null $facebook_feed_targeting = null,
        public string|array|null $facebook_call_to_action = null,
        public string|array|null $facebook_child_attachments = null,
        public ?bool $facebook_multi_share_end_card = null,

        public ?string $pinterest_board_id = null,
        public ?string $pinterest_alt_text = null,
        public ?string $pinterest_link = null,
        public ?string $pinterest_cover_image_url = null,
        public ?string $pinterest_cover_image_content_type = null,
        public ?string $pinterest_cover_image_data = null,
        public int|string|null $pinterest_cover_image_key_frame_time = null,
        public ?string $pinterest_board_section_id = null,
        public string|array|null $pinterest_ai_disclosures = null,
        public array $pinterest_carousel_titles = [],
        public array $pinterest_carousel_descriptions = [],
        public array $pinterest_carousel_links = [],
        public ?int $pinterest_carousel_index = null,

        public XReplySettings|string|null $reply_settings = null,
        public ?bool $nullcast = null,
        public ?string $quote_tweet_id = null,
        public ?string $geo_place_id = null,
        public ?bool $for_super_followers_only = null,
        public ?string $community_id = null,
        public ?bool $share_with_followers = null,
        public ?string $direct_message_deep_link = null,
        public ?bool $x_long_text_as_post = null,
        public array $tagged_user_ids = [],
        public ?string $reply_to_id = null,
        public array $exclude_reply_user_ids = [],
        public ?string $x_thread_image_layout = null,
        public ?string $card_uri = null,
        public array $poll_options = [],
        public int|string|null $poll_duration = null,
        public XReplySettings|string|null $poll_reply_settings = null,
        public ?bool $made_with_ai = null,
        public string|array|null $x_alt_text = null,
        public ?string $x_subtitles = null,
        public ?string $x_subtitles_url = null,
        public ?string $x_subtitles_language = null,
        public ?string $x_subtitles_name = null,
        public ?bool $x_paid_partnership = null,
        public ?string $x_article_title = null,
        public ?string $x_article_body = null,
        public ?string $x_article_content_state = null,
        public ?bool $x_article_draft = null,
        public string|object|int|null $x_article_cover_media = null,

        public ?bool $threads_long_text_as_post = null,
        public ?string $threads_thread_media_layout = null,
        public ?string $threads_topic_tag = null,
        public string|array|null $threads_alt_text = null,
        public ?string $threads_reply_control = null,
        public ?string $threads_reply_to_id = null,
        public ?string $threads_quote_post_id = null,
        public ?string $threads_link_attachment = null,
        public array $threads_poll_options = [],
        public ?bool $threads_auto_publish_text = null,

        public ?string $subreddit = null,
        public ?string $flair_id = null,
        public ?string $reddit_link_url = null,

        public ?string $bluesky_link_url = null,
        public string|array|null $bluesky_alt_text = null,
        public ?string $bluesky_langs = null,
        public ?string $bluesky_labels = null,
        public ?bool $bluesky_gallery = null,
        public ?string $bluesky_threadgate = null,
        public ?string $bluesky_postgate = null,
        public ?string $bluesky_quote_uri = null,

        public ?string $discord_alt_text = null,
        public ?string $discord_thread_id = null,
        public ?string $discord_thread_name = null,
        public ?int $discord_flags = null,
        public ?int $discord_max_file_mb = null,

        public ?string $telegram_parse_mode = null,
        public ?bool $telegram_has_spoiler = null,
        public ?bool $telegram_as_document = null,
        public ?bool $telegram_disable_notification = null,
        public ?bool $telegram_protect_content = null,

        public ?string $gbp_location_id = null,
        public GoogleBusinessTopicType|string|null $gbp_topic_type = null,
        public GoogleBusinessCtaType|string|null $gbp_cta_type = null,
        public ?string $gbp_cta_url = null,
        public ?string $gbp_event_title = null,
        public ?string $gbp_event_start_date = null,
        public ?string $gbp_event_start_time = null,
        public ?string $gbp_event_end_date = null,
        public ?string $gbp_event_end_time = null,
        public ?string $gbp_coupon_code = null,
        public ?string $gbp_redeem_url = null,
        public ?string $gbp_terms = null,
        public GoogleBusinessPostType|string|null $gbp_post_type = null,
        public ?bool $gbp_upload_to_gallery = null,
        public GoogleBusinessMediaCategory|string|null $gbp_media_category = null,
        public ?string $gbp_language_code = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            disable_comment: self::boolOrNull($data['disable_comment'] ?? null),
            brand_content_toggle: self::boolOrNull($data['brand_content_toggle'] ?? null),
            brand_organic_toggle: self::boolOrNull($data['brand_organic_toggle'] ?? null),
            privacy_level: self::stringOrNull($data['privacy_level'] ?? null),
            disable_duet: self::boolOrNull($data['disable_duet'] ?? null),
            disable_stitch: self::boolOrNull($data['disable_stitch'] ?? null),
            cover_timestamp: self::intStringOrNull($data['cover_timestamp'] ?? null),
            is_aigc: self::boolOrNull($data['is_aigc'] ?? null),
            post_mode: self::stringOrNull($data['post_mode'] ?? null),
            auto_add_music: self::boolOrNull($data['auto_add_music'] ?? null),
            photo_cover_index: self::intOrNull($data['photo_cover_index'] ?? null),
            disable_inbox_fallback: self::boolOrNull($data['disable_inbox_fallback'] ?? null),
            tiktok_cover_image: self::mediaInputOrNull($data['tiktok_cover_image'] ?? null),
            tiktok_cover_image_url: self::stringOrNull($data['tiktok_cover_image_url'] ?? null),
            tiktok_is_ads_only: self::boolOrNull($data['tiktok_is_ads_only'] ?? null),
            tiktok_location_id: self::stringOrNull($data['tiktok_location_id'] ?? null),
            tiktok_location_name: self::stringOrNull($data['tiktok_location_name'] ?? null),
            tiktok_music_id: self::stringOrNull($data['tiktok_music_id'] ?? null),
            tiktok_music_start: self::intOrNull($data['tiktok_music_start'] ?? null),
            tiktok_music_end: self::intOrNull($data['tiktok_music_end'] ?? null),
            tiktok_music_volume: self::intOrNull($data['tiktok_music_volume'] ?? null),
            tiktok_original_sound_volume: self::intOrNull($data['tiktok_original_sound_volume'] ?? null),
            tiktok_tto_invite_link: self::stringOrNull($data['tiktok_tto_invite_link'] ?? null),
            media_type: self::stringOrNull($data['media_type'] ?? null),
            collaborators: self::stringOrNull($data['collaborators'] ?? null),
            user_tags: self::stringOrNull($data['user_tags'] ?? null),
            location_id: self::stringOrNull($data['location_id'] ?? null),
            share_mode: self::stringOrNull($data['share_mode'] ?? null),
            share_to_feed: self::boolOrNull($data['share_to_feed'] ?? null),
            cover_url: self::stringOrNull($data['cover_url'] ?? null),
            cover_image: self::mediaInputOrNull($data['cover_image'] ?? null),
            audio_name: self::stringOrNull($data['audio_name'] ?? null),
            thumb_offset: self::stringOrNull($data['thumb_offset'] ?? null),
            instagram_alt_text: self::stringListOrStringOrNull($data['instagram_alt_text'] ?? null),
            tags: self::stringListFrom($data['tags'] ?? []),
            categoryId: self::stringOrNull($data['categoryId'] ?? null),
            privacyStatus: self::stringOrNull($data['privacyStatus'] ?? null),
            embeddable: self::boolOrNull($data['embeddable'] ?? null),
            license: self::stringOrNull($data['license'] ?? null),
            publicStatsViewable: self::boolOrNull($data['publicStatsViewable'] ?? null),
            thumbnail: self::mediaInputOrNull($data['thumbnail'] ?? null),
            thumbnail_url: self::stringOrNull($data['thumbnail_url'] ?? null),
            selfDeclaredMadeForKids: self::boolOrNull($data['selfDeclaredMadeForKids'] ?? null),
            containsSyntheticMedia: self::boolOrNull($data['containsSyntheticMedia'] ?? null),
            defaultLanguage: self::stringOrNull($data['defaultLanguage'] ?? null),
            defaultAudioLanguage: self::stringOrNull($data['defaultAudioLanguage'] ?? null),
            allowedCountries: self::stringOrNull($data['allowedCountries'] ?? null),
            blockedCountries: self::stringOrNull($data['blockedCountries'] ?? null),
            hasPaidProductPlacement: self::boolOrNull($data['hasPaidProductPlacement'] ?? null),
            recordingDate: self::stringOrNull($data['recordingDate'] ?? null),
            youtube_playlist_id: self::stringOrNull($data['youtube_playlist_id'] ?? null),
            youtube_subtitles: self::youtubeSubtitlesFrom($data['youtube_subtitles'] ?? []),
            youtube_notify_subscribers: self::boolOrNull($data['youtube_notify_subscribers'] ?? null),
            youtube_publish_at: self::stringOrNull($data['youtube_publish_at'] ?? null),
            visibility: self::stringOrNull($data['visibility'] ?? null),
            target_linkedin_page_id: self::stringOrNull($data['target_linkedin_page_id'] ?? null),
            linkedin_link_url: self::stringOrNull($data['linkedin_link_url'] ?? null),
            linkedin_disable_reshare: self::boolOrNull($data['linkedin_disable_reshare'] ?? null),
            linkedin_alt_text: self::stringListOrStringOrNull($data['linkedin_alt_text'] ?? null),
            linkedin_subtitles: self::mediaInputOrNull($data['linkedin_subtitles'] ?? null),
            linkedin_subtitles_url: self::stringOrNull($data['linkedin_subtitles_url'] ?? null),
            linkedin_subtitles_text: self::stringOrNull($data['linkedin_subtitles_text'] ?? null),
            linkedin_link_title: self::stringOrNull($data['linkedin_link_title'] ?? null),
            linkedin_link_description: self::stringOrNull($data['linkedin_link_description'] ?? null),
            linkedin_thumbnail_alt_text: self::stringOrNull($data['linkedin_thumbnail_alt_text'] ?? null),
            linkedin_poll_question: self::stringOrNull($data['linkedin_poll_question'] ?? null),
            linkedin_poll_options: self::stringListFrom($data['linkedin_poll_options'] ?? []),
            linkedin_poll_duration: self::linkedinPollDurationFrom($data['linkedin_poll_duration'] ?? null),
            facebook_page_id: self::stringOrNull($data['facebook_page_id'] ?? null),
            video_state: self::stringOrNull($data['video_state'] ?? null),
            facebook_media_type: self::stringOrNull($data['facebook_media_type'] ?? null),
            facebook_link_url: self::stringOrNull($data['facebook_link_url'] ?? null),
            facebook_collaborators: self::stringListOrStringOrNull($data['facebook_collaborators'] ?? null),
            facebook_is_ai_generated: self::boolOrNull($data['facebook_is_ai_generated'] ?? null),
            facebook_unpublished_content_type: self::stringOrNull($data['facebook_unpublished_content_type'] ?? null),
            facebook_no_story: self::boolOrNull($data['facebook_no_story'] ?? null),
            facebook_secret: self::boolOrNull($data['facebook_secret'] ?? null),
            facebook_alt_text: self::stringListOrStringOrNull($data['facebook_alt_text'] ?? null),
            facebook_place_id: self::stringOrNull($data['facebook_place_id'] ?? null),
            facebook_targeting: self::arrayOrStringOrNull($data['facebook_targeting'] ?? null),
            facebook_feed_targeting: self::arrayOrStringOrNull($data['facebook_feed_targeting'] ?? null),
            facebook_call_to_action: self::arrayOrStringOrNull($data['facebook_call_to_action'] ?? null),
            facebook_child_attachments: self::arrayListOrStringOrNull($data['facebook_child_attachments'] ?? null),
            facebook_multi_share_end_card: self::boolOrNull($data['facebook_multi_share_end_card'] ?? null),
            pinterest_board_id: self::stringOrNull($data['pinterest_board_id'] ?? null),
            pinterest_alt_text: self::stringOrNull($data['pinterest_alt_text'] ?? null),
            pinterest_link: self::stringOrNull($data['pinterest_link'] ?? null),
            pinterest_cover_image_url: self::stringOrNull($data['pinterest_cover_image_url'] ?? null),
            pinterest_cover_image_content_type: self::stringOrNull($data['pinterest_cover_image_content_type'] ?? null),
            pinterest_cover_image_data: self::stringOrNull($data['pinterest_cover_image_data'] ?? null),
            pinterest_cover_image_key_frame_time: self::intStringOrNull($data['pinterest_cover_image_key_frame_time'] ?? null),
            pinterest_board_section_id: self::stringOrNull($data['pinterest_board_section_id'] ?? null),
            pinterest_ai_disclosures: self::stringListOrStringOrNull($data['pinterest_ai_disclosures'] ?? null),
            pinterest_carousel_titles: self::stringListFrom($data['pinterest_carousel_titles'] ?? []),
            pinterest_carousel_descriptions: self::stringListFrom($data['pinterest_carousel_descriptions'] ?? []),
            pinterest_carousel_links: self::stringListFrom($data['pinterest_carousel_links'] ?? []),
            pinterest_carousel_index: self::intOrNull($data['pinterest_carousel_index'] ?? null),
            reply_settings: self::stringOrNull($data['reply_settings'] ?? null),
            nullcast: self::boolOrNull($data['nullcast'] ?? null),
            quote_tweet_id: self::stringOrNull($data['quote_tweet_id'] ?? null),
            geo_place_id: self::stringOrNull($data['geo_place_id'] ?? null),
            for_super_followers_only: self::boolOrNull($data['for_super_followers_only'] ?? null),
            community_id: self::stringOrNull($data['community_id'] ?? null),
            share_with_followers: self::boolOrNull($data['share_with_followers'] ?? null),
            direct_message_deep_link: self::stringOrNull($data['direct_message_deep_link'] ?? null),
            x_long_text_as_post: self::boolOrNull($data['x_long_text_as_post'] ?? null),
            tagged_user_ids: self::stringListFrom($data['tagged_user_ids'] ?? []),
            reply_to_id: self::stringOrNull($data['reply_to_id'] ?? null),
            exclude_reply_user_ids: self::stringListFrom($data['exclude_reply_user_ids'] ?? []),
            x_thread_image_layout: self::stringOrNull($data['x_thread_image_layout'] ?? null),
            card_uri: self::stringOrNull($data['card_uri'] ?? null),
            poll_options: self::stringListFrom($data['poll_options'] ?? []),
            poll_duration: self::intStringOrNull($data['poll_duration'] ?? null),
            poll_reply_settings: self::stringOrNull($data['poll_reply_settings'] ?? null),
            made_with_ai: self::boolOrNull($data['made_with_ai'] ?? null),
            x_alt_text: self::stringListOrStringOrNull($data['x_alt_text'] ?? null),
            x_subtitles: self::stringOrNull($data['x_subtitles'] ?? null),
            x_subtitles_url: self::stringOrNull($data['x_subtitles_url'] ?? null),
            x_subtitles_language: self::stringOrNull($data['x_subtitles_language'] ?? null),
            x_subtitles_name: self::stringOrNull($data['x_subtitles_name'] ?? null),
            x_paid_partnership: self::boolOrNull($data['x_paid_partnership'] ?? null),
            x_article_title: self::stringOrNull($data['x_article_title'] ?? null),
            x_article_body: self::stringOrNull($data['x_article_body'] ?? null),
            x_article_content_state: self::stringOrNull($data['x_article_content_state'] ?? null),
            x_article_draft: self::boolOrNull($data['x_article_draft'] ?? null),
            x_article_cover_media: self::articleCoverMediaOrNull($data['x_article_cover_media'] ?? null),
            threads_long_text_as_post: self::boolOrNull($data['threads_long_text_as_post'] ?? null),
            threads_thread_media_layout: self::stringOrNull($data['threads_thread_media_layout'] ?? null),
            threads_topic_tag: self::stringOrNull($data['threads_topic_tag'] ?? null),
            threads_alt_text: self::stringListOrStringOrNull($data['threads_alt_text'] ?? null),
            threads_reply_control: self::stringOrNull($data['threads_reply_control'] ?? null),
            threads_reply_to_id: self::stringOrNull($data['threads_reply_to_id'] ?? null),
            threads_quote_post_id: self::stringOrNull($data['threads_quote_post_id'] ?? null),
            threads_link_attachment: self::stringOrNull($data['threads_link_attachment'] ?? null),
            threads_poll_options: self::stringListFrom($data['threads_poll_options'] ?? []),
            threads_auto_publish_text: self::boolOrNull($data['threads_auto_publish_text'] ?? null),
            subreddit: self::stringOrNull($data['subreddit'] ?? null),
            flair_id: self::stringOrNull($data['flair_id'] ?? null),
            reddit_link_url: self::stringOrNull($data['reddit_link_url'] ?? null),
            bluesky_link_url: self::stringOrNull($data['bluesky_link_url'] ?? null),
            bluesky_alt_text: self::stringListOrStringOrNull($data['bluesky_alt_text'] ?? null),
            bluesky_langs: self::stringOrNull($data['bluesky_langs'] ?? null),
            bluesky_labels: self::stringOrNull($data['bluesky_labels'] ?? null),
            bluesky_gallery: self::boolOrNull($data['bluesky_gallery'] ?? null),
            bluesky_threadgate: self::stringOrNull($data['bluesky_threadgate'] ?? null),
            bluesky_postgate: self::stringOrNull($data['bluesky_postgate'] ?? null),
            bluesky_quote_uri: self::stringOrNull($data['bluesky_quote_uri'] ?? null),
            discord_alt_text: self::stringOrNull($data['discord_alt_text'] ?? null),
            discord_thread_id: self::stringOrNull($data['discord_thread_id'] ?? null),
            discord_thread_name: self::stringOrNull($data['discord_thread_name'] ?? null),
            discord_flags: self::intOrNull($data['discord_flags'] ?? null),
            discord_max_file_mb: self::intOrNull($data['discord_max_file_mb'] ?? null),
            telegram_parse_mode: self::stringOrNull($data['telegram_parse_mode'] ?? null),
            telegram_has_spoiler: self::boolOrNull($data['telegram_has_spoiler'] ?? null),
            telegram_as_document: self::boolOrNull($data['telegram_as_document'] ?? null),
            telegram_disable_notification: self::boolOrNull($data['telegram_disable_notification'] ?? null),
            telegram_protect_content: self::boolOrNull($data['telegram_protect_content'] ?? null),
            gbp_location_id: self::stringOrNull($data['gbp_location_id'] ?? null),
            gbp_topic_type: self::stringOrNull($data['gbp_topic_type'] ?? null),
            gbp_cta_type: self::stringOrNull($data['gbp_cta_type'] ?? null),
            gbp_cta_url: self::stringOrNull($data['gbp_cta_url'] ?? null),
            gbp_event_title: self::stringOrNull($data['gbp_event_title'] ?? null),
            gbp_event_start_date: self::stringOrNull($data['gbp_event_start_date'] ?? null),
            gbp_event_start_time: self::stringOrNull($data['gbp_event_start_time'] ?? null),
            gbp_event_end_date: self::stringOrNull($data['gbp_event_end_date'] ?? null),
            gbp_event_end_time: self::stringOrNull($data['gbp_event_end_time'] ?? null),
            gbp_coupon_code: self::stringOrNull($data['gbp_coupon_code'] ?? null),
            gbp_redeem_url: self::stringOrNull($data['gbp_redeem_url'] ?? null),
            gbp_terms: self::stringOrNull($data['gbp_terms'] ?? null),
            gbp_post_type: self::stringOrNull($data['gbp_post_type'] ?? null),
            gbp_upload_to_gallery: self::boolOrNull($data['gbp_upload_to_gallery'] ?? null),
            gbp_media_category: self::stringOrNull($data['gbp_media_category'] ?? $data['media_category'] ?? null),
            gbp_language_code: self::stringOrNull($data['gbp_language_code'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::withoutBlankValues([
            'disable_comment' => $this->disable_comment,
            'brand_content_toggle' => $this->brand_content_toggle,
            'brand_organic_toggle' => $this->brand_organic_toggle,
            'privacy_level' => self::enumValue($this->privacy_level),
            'disable_duet' => $this->disable_duet,
            'disable_stitch' => $this->disable_stitch,
            'cover_timestamp' => $this->cover_timestamp,
            'is_aigc' => $this->is_aigc,
            'post_mode' => self::enumValue($this->post_mode),
            'auto_add_music' => $this->auto_add_music,
            'photo_cover_index' => $this->photo_cover_index,
            'disable_inbox_fallback' => $this->disable_inbox_fallback,
            'tiktok_cover_image' => $this->tiktok_cover_image,
            'tiktok_cover_image_url' => $this->tiktok_cover_image_url,
            'tiktok_is_ads_only' => $this->tiktok_is_ads_only,
            'tiktok_location_id' => $this->tiktok_location_id,
            'tiktok_location_name' => $this->tiktok_location_name,
            'tiktok_music_id' => $this->tiktok_music_id,
            'tiktok_music_start' => $this->tiktok_music_start,
            'tiktok_music_end' => $this->tiktok_music_end,
            'tiktok_music_volume' => $this->tiktok_music_volume,
            'tiktok_original_sound_volume' => $this->tiktok_original_sound_volume,
            'tiktok_tto_invite_link' => $this->tiktok_tto_invite_link,
            'media_type' => self::enumValue($this->media_type),
            'collaborators' => $this->collaborators,
            'user_tags' => $this->user_tags,
            'location_id' => $this->location_id,
            'share_mode' => $this->share_mode,
            'share_to_feed' => $this->share_to_feed,
            'cover_url' => $this->cover_url,
            'cover_image' => $this->cover_image,
            'audio_name' => $this->audio_name,
            'thumb_offset' => $this->thumb_offset,
            'instagram_alt_text' => $this->instagram_alt_text,
            'tags' => $this->tags,
            'categoryId' => $this->categoryId,
            'privacyStatus' => self::enumValue($this->privacyStatus),
            'embeddable' => $this->embeddable,
            'license' => $this->license,
            'publicStatsViewable' => $this->publicStatsViewable,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail_url,
            'selfDeclaredMadeForKids' => $this->selfDeclaredMadeForKids,
            'containsSyntheticMedia' => $this->containsSyntheticMedia,
            'defaultLanguage' => $this->defaultLanguage,
            'defaultAudioLanguage' => $this->defaultAudioLanguage,
            'allowedCountries' => $this->allowedCountries,
            'blockedCountries' => $this->blockedCountries,
            'hasPaidProductPlacement' => $this->hasPaidProductPlacement,
            'recordingDate' => $this->recordingDate,
            'youtube_playlist_id' => $this->youtube_playlist_id,
            'youtube_subtitles' => array_map(
                static fn (YoutubeSubtitleData $subtitle): array => $subtitle->toArray(),
                $this->youtube_subtitles
            ),
            'youtube_notify_subscribers' => $this->youtube_notify_subscribers,
            'youtube_publish_at' => $this->youtube_publish_at,
            'visibility' => self::enumValue($this->visibility),
            'target_linkedin_page_id' => $this->target_linkedin_page_id,
            'linkedin_link_url' => $this->linkedin_link_url,
            'linkedin_disable_reshare' => $this->linkedin_disable_reshare,
            'linkedin_alt_text' => $this->linkedin_alt_text,
            'linkedin_subtitles' => $this->linkedin_subtitles,
            'linkedin_subtitles_url' => $this->linkedin_subtitles_url,
            'linkedin_subtitles_text' => $this->linkedin_subtitles_text,
            'linkedin_link_title' => $this->linkedin_link_title,
            'linkedin_link_description' => $this->linkedin_link_description,
            'linkedin_thumbnail_alt_text' => $this->linkedin_thumbnail_alt_text,
            'linkedin_poll_question' => $this->linkedin_poll_question,
            'linkedin_poll_options' => $this->linkedin_poll_options,
            'linkedin_poll_duration' => self::enumValue($this->linkedin_poll_duration),
            'facebook_page_id' => $this->facebook_page_id,
            'video_state' => self::enumValue($this->video_state),
            'facebook_media_type' => self::enumValue($this->facebook_media_type),
            'facebook_link_url' => $this->facebook_link_url,
            'facebook_collaborators' => $this->facebook_collaborators,
            'facebook_is_ai_generated' => $this->facebook_is_ai_generated,
            'facebook_unpublished_content_type' => $this->facebook_unpublished_content_type,
            'facebook_no_story' => $this->facebook_no_story,
            'facebook_secret' => $this->facebook_secret,
            'facebook_alt_text' => $this->facebook_alt_text,
            'facebook_place_id' => $this->facebook_place_id,
            'facebook_targeting' => $this->facebook_targeting,
            'facebook_feed_targeting' => $this->facebook_feed_targeting,
            'facebook_call_to_action' => $this->facebook_call_to_action,
            'facebook_child_attachments' => $this->facebook_child_attachments,
            'facebook_multi_share_end_card' => $this->facebook_multi_share_end_card,
            'pinterest_board_id' => $this->pinterest_board_id,
            'pinterest_alt_text' => $this->pinterest_alt_text,
            'pinterest_link' => $this->pinterest_link,
            'pinterest_cover_image_url' => $this->pinterest_cover_image_url,
            'pinterest_cover_image_content_type' => $this->pinterest_cover_image_content_type,
            'pinterest_cover_image_data' => $this->pinterest_cover_image_data,
            'pinterest_cover_image_key_frame_time' => $this->pinterest_cover_image_key_frame_time,
            'pinterest_board_section_id' => $this->pinterest_board_section_id,
            'pinterest_ai_disclosures' => $this->pinterest_ai_disclosures,
            'pinterest_carousel_titles' => $this->pinterest_carousel_titles,
            'pinterest_carousel_descriptions' => $this->pinterest_carousel_descriptions,
            'pinterest_carousel_links' => $this->pinterest_carousel_links,
            'pinterest_carousel_index' => $this->pinterest_carousel_index,
            'reply_settings' => self::enumValue($this->reply_settings),
            'nullcast' => $this->nullcast,
            'quote_tweet_id' => $this->quote_tweet_id,
            'geo_place_id' => $this->geo_place_id,
            'for_super_followers_only' => $this->for_super_followers_only,
            'community_id' => $this->community_id,
            'share_with_followers' => $this->share_with_followers,
            'direct_message_deep_link' => $this->direct_message_deep_link,
            'x_long_text_as_post' => $this->x_long_text_as_post,
            'tagged_user_ids' => $this->tagged_user_ids,
            'reply_to_id' => $this->reply_to_id,
            'exclude_reply_user_ids' => $this->exclude_reply_user_ids,
            'x_thread_image_layout' => $this->x_thread_image_layout,
            'card_uri' => $this->card_uri,
            'poll_options' => $this->poll_options,
            'poll_duration' => $this->poll_duration,
            'poll_reply_settings' => self::enumValue($this->poll_reply_settings),
            'made_with_ai' => $this->made_with_ai,
            'x_alt_text' => $this->x_alt_text,
            'x_subtitles' => $this->x_subtitles,
            'x_subtitles_url' => $this->x_subtitles_url,
            'x_subtitles_language' => $this->x_subtitles_language,
            'x_subtitles_name' => $this->x_subtitles_name,
            'x_paid_partnership' => $this->x_paid_partnership,
            'x_article_title' => $this->x_article_title,
            'x_article_body' => $this->x_article_body,
            'x_article_content_state' => $this->x_article_content_state,
            'x_article_draft' => $this->x_article_draft,
            'x_article_cover_media' => $this->x_article_cover_media,
            'threads_long_text_as_post' => $this->threads_long_text_as_post,
            'threads_thread_media_layout' => $this->threads_thread_media_layout,
            'threads_topic_tag' => $this->threads_topic_tag,
            'threads_alt_text' => $this->threads_alt_text,
            'threads_reply_control' => $this->threads_reply_control,
            'threads_reply_to_id' => $this->threads_reply_to_id,
            'threads_quote_post_id' => $this->threads_quote_post_id,
            'threads_link_attachment' => $this->threads_link_attachment,
            'threads_poll_options' => $this->threads_poll_options,
            'threads_auto_publish_text' => $this->threads_auto_publish_text,
            'subreddit' => $this->subreddit,
            'flair_id' => $this->flair_id,
            'reddit_link_url' => $this->reddit_link_url,
            'bluesky_link_url' => $this->bluesky_link_url,
            'bluesky_alt_text' => $this->bluesky_alt_text,
            'bluesky_langs' => $this->bluesky_langs,
            'bluesky_labels' => $this->bluesky_labels,
            'bluesky_gallery' => $this->bluesky_gallery,
            'bluesky_threadgate' => $this->bluesky_threadgate,
            'bluesky_postgate' => $this->bluesky_postgate,
            'bluesky_quote_uri' => $this->bluesky_quote_uri,
            'discord_alt_text' => $this->discord_alt_text,
            'discord_thread_id' => $this->discord_thread_id,
            'discord_thread_name' => $this->discord_thread_name,
            'discord_flags' => $this->discord_flags,
            'discord_max_file_mb' => $this->discord_max_file_mb,
            'telegram_parse_mode' => $this->telegram_parse_mode,
            'telegram_has_spoiler' => $this->telegram_has_spoiler,
            'telegram_as_document' => $this->telegram_as_document,
            'telegram_disable_notification' => $this->telegram_disable_notification,
            'telegram_protect_content' => $this->telegram_protect_content,
            'gbp_location_id' => $this->gbp_location_id,
            'gbp_topic_type' => $this->googleBusinessEnumValue($this->gbp_topic_type),
            'gbp_cta_type' => $this->googleBusinessEnumValue($this->gbp_cta_type),
            'gbp_cta_url' => $this->gbp_cta_url,
            'gbp_post_type' => $this->googleBusinessEnumValue($this->gbp_post_type),
            'gbp_upload_to_gallery' => $this->gbp_upload_to_gallery,
            'gbp_media_category' => $this->googleBusinessEnumValue($this->gbp_media_category),
            'gbp_language_code' => $this->gbp_language_code,
            'gbp_event_title' => $this->gbp_event_title,
            'gbp_event_start_date' => $this->gbp_event_start_date,
            'gbp_event_start_time' => $this->gbp_event_start_time,
            'gbp_event_end_date' => $this->gbp_event_end_date,
            'gbp_event_end_time' => $this->gbp_event_end_time,
            'gbp_coupon_code' => $this->gbp_coupon_code,
            'gbp_redeem_url' => $this->gbp_redeem_url,
            'gbp_terms' => $this->gbp_terms,
        ]);
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function validateForVideo(array $platforms): void
    {
        $this->validateShared($platforms);

        if ($this->hasPlatform($platforms, Platform::GoogleBusiness) && $this->usesGoogleBusinessGallery()) {
            throw new InvalidArgumentException('Google Business gallery uploads require photo media.');
        }

        if ($this->hasPlatform($platforms, Platform::X) && $this->hasText($this->quote_tweet_id)) {
            throw new InvalidArgumentException('quote_tweet_id cannot be used with X video uploads.');
        }

        if (
            $this->hasPlatform($platforms, Platform::YouTube) &&
            ($this->hasText($this->allowedCountries) && $this->hasText($this->blockedCountries))
        ) {
            throw new InvalidArgumentException('allowedCountries and blockedCountries cannot be used together.');
        }

        if ($this->hasPlatform($platforms, Platform::Instagram)) {
            $this->validateMediaType($this->media_type, ['REELS', 'STORIES'], 'Instagram video');

            if (self::mediaIsUrl($this->cover_image)) {
                throw new InvalidArgumentException('URLs must use cover_url instead of cover_image for Instagram video uploads.');
            }
        }

        if ($this->hasPlatform($platforms, Platform::TikTok) && self::mediaIsUrl($this->tiktok_cover_image)) {
            throw new InvalidArgumentException('URLs must use tiktok_cover_image_url instead of tiktok_cover_image.');
        }

        if ($this->hasPlatform($platforms, Platform::LinkedIn) && self::mediaIsUrl($this->linkedin_subtitles)) {
            throw new InvalidArgumentException('URLs must use linkedin_subtitles_url instead of linkedin_subtitles.');
        }

        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->validateMediaType($this->facebook_media_type, ['REELS', 'STORIES', 'VIDEO'], 'Facebook video');
        }

        if ($this->hasPlatform($platforms, Platform::Pinterest)) {
            $hasData = $this->hasText($this->pinterest_cover_image_data);
            $hasContentType = $this->hasText($this->pinterest_cover_image_content_type);

            if ($hasData !== $hasContentType) {
                throw new InvalidArgumentException('pinterest_cover_image_data and pinterest_cover_image_content_type must be used together.');
            }
        }
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function validateForPhotos(array $platforms): void
    {
        $this->validateShared($platforms);

        if ($this->hasPlatform($platforms, Platform::X) && $this->hasText($this->quote_tweet_id)) {
            throw new InvalidArgumentException('quote_tweet_id cannot be used with X photo uploads.');
        }

        if ($this->hasPlatform($platforms, Platform::Instagram)) {
            $this->validateMediaType($this->media_type, ['IMAGE', 'STORIES'], 'Instagram photo');
        }

        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->validateMediaType($this->facebook_media_type, ['POSTS', 'STORIES'], 'Facebook photo');
        }

        if ($this->hasPlatform($platforms, Platform::LinkedIn)) {
            $this->validateLinkedinVisibility('photo uploads');
        }
    }

    public function validateForDocument(): void
    {
        $this->validateLinkedinVisibility('document uploads');
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    public function validateForText(array $platforms, ?string $link_url = null): void
    {
        $this->validateShared($platforms);

        if ($this->hasPlatform($platforms, Platform::X)) {
            $hasArticleTitle = $this->hasText($this->x_article_title);
            $hasArticleFields = $this->x_article_body !== null
                || $this->x_article_content_state !== null
                || $this->x_article_draft !== null
                || $this->x_article_cover_media !== null;

            if (! $hasArticleTitle && $hasArticleFields) {
                throw new InvalidArgumentException('x_article_body, x_article_content_state, x_article_draft, and x_article_cover_media require x_article_title.');
            }

            if ($hasArticleTitle) {
                if (mb_strlen(trim((string) $this->x_article_title)) > 100) {
                    throw new InvalidArgumentException('x_article_title must be 100 characters or fewer.');
                }

                $articleConflicts = array_filter([
                    'poll_options' => $this->poll_options !== [],
                    'quote_tweet_id' => $this->hasText($this->quote_tweet_id),
                    'reply_to_id' => $this->hasText($this->reply_to_id),
                    'card_uri' => $this->hasText($this->card_uri),
                    'direct_message_deep_link' => $this->hasText($this->direct_message_deep_link),
                ]);

                if ($articleConflicts !== []) {
                    throw new InvalidArgumentException('x_article_title cannot be combined with poll_options, quote_tweet_id, reply_to_id, card_uri, or direct_message_deep_link.');
                }
            }

            $exclusiveOptions = array_filter([
                'quote_tweet_id' => $this->hasText($this->quote_tweet_id),
                'card_uri' => $this->hasText($this->card_uri),
                'direct_message_deep_link' => $this->hasText($this->direct_message_deep_link),
                'poll_options' => $this->poll_options !== [],
            ]);

            if (count($exclusiveOptions) > 1) {
                throw new InvalidArgumentException('quote_tweet_id, card_uri, direct_message_deep_link, and poll_options are mutually exclusive for X text uploads.');
            }

            if (
                $this->poll_options !== [] &&
                (count($this->poll_options) < 2 || count($this->poll_options) > 4)
            ) {
                throw new InvalidArgumentException('poll_options must contain between 2 and 4 options.');
            }

            foreach ($this->poll_options as $option) {
                if (mb_strlen($option) > 25) {
                    throw new InvalidArgumentException('Each X poll option must be 25 characters or fewer.');
                }
            }

            if ($this->poll_duration !== null) {
                $duration = filter_var($this->poll_duration, FILTER_VALIDATE_INT);

                if ($duration === false || $duration < 5 || $duration > 10080) {
                    throw new InvalidArgumentException('poll_duration must be between 5 and 10080 minutes.');
                }
            }

            if ($this->poll_reply_settings !== null && $this->poll_options === []) {
                throw new InvalidArgumentException('poll_reply_settings requires poll_options.');
            }
        }

        if ($this->hasPlatform($platforms, Platform::LinkedIn)) {
            $this->validateLinkedinPoll($link_url);
        }

        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->validateFacebookCallToAction($link_url);
        }

        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->validateThreadsTextOptions();
        }

        if ($this->hasPlatform($platforms, Platform::GoogleBusiness) && $this->usesGoogleBusinessGallery()) {
            throw new InvalidArgumentException('Google Business gallery uploads require photo media.');
        }
    }

    /**
     * @param  list<Platform|string>  $platforms
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
            $this->addLinkedin($payload, false, null, true);
        }
        if ($this->hasPlatform($platforms, Platform::Facebook)) {
            $this->addFacebook($payload, true, false);
        }
        if ($this->hasPlatform($platforms, Platform::Pinterest)) {
            $this->addPinterest($payload, true);
        }
        if ($this->hasPlatform($platforms, Platform::X)) {
            $this->addX($payload, is_text: false, is_photo: false);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload, is_text: false, is_photo: false);
        }
        if ($this->hasPlatform($platforms, Platform::GoogleBusiness)) {
            $this->addGoogleBusiness($payload);
        }

        $this->addScopedOptions($payload, $platforms, 'video');

        return $payload;
    }

    /**
     * @param  list<Platform|string>  $platforms
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
            $this->addX($payload, is_text: false, is_photo: true);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload, is_text: false, is_photo: true);
        }
        if ($this->hasPlatform($platforms, Platform::GoogleBusiness)) {
            $this->addGoogleBusiness($payload);
        }

        $this->addScopedOptions($payload, $platforms, 'photos');

        return $payload;
    }

    /**
     * @param  list<Platform|string>  $platforms
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
            $this->addX($payload, is_text: true, is_photo: false);
        }
        if ($this->hasPlatform($platforms, Platform::Threads)) {
            $this->addThreads($payload, is_text: true, is_photo: false);
        }
        if ($this->hasPlatform($platforms, Platform::Bluesky)) {
            $this->addBluesky($payload, $link_url, ! $this->hasPlatform($platforms, Platform::X));
        }
        if ($this->hasPlatform($platforms, Platform::GoogleBusiness)) {
            $this->addGoogleBusiness($payload);
        }

        $this->addScopedOptions($payload, $platforms, 'text');

        return $payload;
    }

    public function addForDocument(MultipartPayload $payload): MultipartPayload
    {
        return $this->addLinkedin($payload, false);
    }

    /**
     * @return list<YoutubeSubtitleData>
     */
    private static function youtubeSubtitlesFrom(mixed $value): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $subtitle): ?YoutubeSubtitleData => match (true) {
                    $subtitle instanceof YoutubeSubtitleData => $subtitle,
                    is_array($subtitle) => YoutubeSubtitleData::fromArray($subtitle),
                    default => null,
                },
                self::listFrom($value)
            )
        ));
    }

    /**
     * @return array<array-key, mixed>|string|null
     */
    private static function arrayOrStringOrNull(mixed $value): array|string|null
    {
        return is_array($value) ? $value : self::stringOrNull($value);
    }

    /**
     * @return list<string>|string|null
     */
    private static function stringListOrStringOrNull(mixed $value): array|string|null
    {
        return is_array($value) ? self::stringListFrom($value) : self::stringOrNull($value);
    }

    /**
     * @return list<array<string, mixed>>|string|null
     */
    private static function arrayListOrStringOrNull(mixed $value): array|string|null
    {
        if (! is_array($value)) {
            return self::stringOrNull($value);
        }

        return array_values(array_filter($value, is_array(...)));
    }

    private static function articleCoverMediaOrNull(mixed $value): string|object|int|null
    {
        return is_string($value) || is_object($value) || is_int($value) ? $value : null;
    }

    private static function linkedinPollDurationFrom(mixed $value): LinkedinPollDuration|int|string|null
    {
        if ($value instanceof LinkedinPollDuration || is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $enum = LinkedinPollDuration::tryFrom(strtoupper($value));

        if ($enum !== null) {
            return $enum;
        }

        return ctype_digit($value) ? (int) $value : $value;
    }

    private function googleBusinessEnumValue(mixed $value): mixed
    {
        $value = self::enumValue($value);

        return is_string($value) ? strtoupper(trim($value)) : $value;
    }

    /**
     * Serializes explicit options only where the API documents support for the
     * selected platform and upload type.
     *
     * @param  list<Platform|string>  $platforms
     */
    private function addScopedOptions(MultipartPayload $payload, array $platforms, string $uploadType): void
    {
        $values = $this->toArray();

        foreach ($this->scopedOptionNames($uploadType, $this->platformValues($platforms)) as $field) {
            $value = $values[$field] ?? null;
            if ($value === null) {
                continue;
            }

            if (in_array($field, ['tiktok_cover_image', 'linkedin_subtitles'], true)) {
                $payload->media($field, $value instanceof Media ? $value : Media::from($value));

                continue;
            }

            if ($field === 'x_article_cover_media' && ! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
                $payload->media($field, $value instanceof Media ? $value : Media::from($value));

                continue;
            }

            if (is_array($value) && $this->isJsonScopedField($field)) {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            }

            $payload->field($this->isScopedList($field) ? $field.'[]' : $field, $value);
        }
    }

    /** @param  list<string>  $platforms
     * @return list<string>
     */
    private function scopedOptionNames(string $uploadType, array $platforms): array
    {
        $byType = [
            'video' => [
                'tiktok' => ['disable_inbox_fallback', 'tiktok_cover_image', 'tiktok_cover_image_url', 'tiktok_is_ads_only', 'tiktok_location_id', 'tiktok_location_name', 'tiktok_music_id', 'tiktok_music_start', 'tiktok_music_end', 'tiktok_music_volume', 'tiktok_original_sound_volume', 'tiktok_tto_invite_link'],
                'youtube' => ['youtube_notify_subscribers', 'youtube_publish_at'],
                'linkedin' => ['linkedin_disable_reshare', 'linkedin_subtitles', 'linkedin_subtitles_url', 'linkedin_subtitles_text'],
                'facebook' => ['facebook_collaborators', 'facebook_is_ai_generated', 'facebook_unpublished_content_type', 'facebook_no_story', 'facebook_secret'],
                'pinterest' => ['pinterest_board_section_id', 'pinterest_ai_disclosures'],
                'threads' => ['threads_alt_text', 'threads_reply_control', 'threads_reply_to_id', 'threads_quote_post_id'],
                'bluesky' => ['bluesky_alt_text', 'bluesky_langs', 'bluesky_labels', 'bluesky_threadgate', 'bluesky_postgate', 'bluesky_quote_uri'],
                'discord' => ['discord_alt_text', 'discord_thread_id', 'discord_max_file_mb'],
                'telegram' => ['telegram_parse_mode', 'telegram_has_spoiler', 'telegram_as_document'],
                'x' => ['made_with_ai', 'x_alt_text', 'x_subtitles', 'x_subtitles_url', 'x_subtitles_language', 'x_subtitles_name', 'x_paid_partnership'],
            ],
            'photos' => [
                'tiktok' => ['disable_inbox_fallback', 'tiktok_location_id', 'tiktok_location_name', 'tiktok_music_id'],
                'instagram' => ['instagram_alt_text'], 'linkedin' => ['linkedin_disable_reshare', 'linkedin_alt_text'],
                'facebook' => ['facebook_alt_text', 'facebook_place_id', 'facebook_targeting', 'facebook_feed_targeting', 'facebook_no_story', 'facebook_secret'],
                'pinterest' => ['pinterest_board_section_id', 'pinterest_ai_disclosures', 'pinterest_carousel_titles', 'pinterest_carousel_descriptions', 'pinterest_carousel_links', 'pinterest_carousel_index'],
                'threads' => ['threads_alt_text', 'threads_reply_control', 'threads_reply_to_id', 'threads_quote_post_id'],
                'bluesky' => ['bluesky_alt_text', 'bluesky_langs', 'bluesky_labels', 'bluesky_gallery', 'bluesky_threadgate', 'bluesky_postgate', 'bluesky_quote_uri'],
                'discord' => ['discord_alt_text', 'discord_thread_id', 'discord_thread_name', 'discord_flags'],
                'telegram' => ['telegram_parse_mode', 'telegram_has_spoiler', 'telegram_disable_notification', 'telegram_protect_content'],
                'x' => ['made_with_ai', 'x_alt_text', 'x_paid_partnership'],
            ],
            'text' => [
                'linkedin' => ['linkedin_disable_reshare', 'linkedin_link_title', 'linkedin_link_description', 'linkedin_thumbnail_alt_text', 'linkedin_poll_question', 'linkedin_poll_options', 'linkedin_poll_duration'],
                'facebook' => ['facebook_call_to_action', 'facebook_child_attachments', 'facebook_multi_share_end_card', 'facebook_place_id'],
                'threads' => ['threads_reply_control', 'threads_reply_to_id', 'threads_quote_post_id', 'threads_link_attachment', 'threads_poll_options', 'threads_auto_publish_text'],
                'bluesky' => ['bluesky_langs', 'bluesky_labels', 'bluesky_threadgate', 'bluesky_postgate', 'bluesky_quote_uri'],
                'x' => ['x_paid_partnership', 'x_article_title', 'x_article_body', 'x_article_content_state', 'x_article_draft', 'x_article_cover_media'],
            ],
        ];

        $fields = [];
        foreach ($byType[$uploadType] ?? [] as $platform => $options) {
            if (in_array($platform, $platforms, true)) {
                array_push($fields, ...$options);
            }
        }

        return $fields;
    }

    /** @param  list<Platform|string>  $platforms
     * @return list<string>
     */
    private function platformValues(array $platforms): array
    {
        return self::platformsToValues($platforms);
    }

    private function isScopedList(string $field): bool
    {
        return in_array($field, [
            'linkedin_poll_options',
            'pinterest_ai_disclosures',
            'pinterest_carousel_titles',
            'pinterest_carousel_descriptions',
            'pinterest_carousel_links',
            'facebook_collaborators',
            'facebook_alt_text',
            'x_alt_text',
            'threads_alt_text',
            'threads_poll_options',
        ], true);
    }

    private function isJsonScopedField(string $field): bool
    {
        return in_array($field, [
            'instagram_alt_text',
            'bluesky_alt_text',
            'facebook_targeting',
            'facebook_feed_targeting',
            'facebook_call_to_action',
            'facebook_child_attachments',
        ], true);
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    private function validateShared(array $platforms): void
    {
        if (
            $this->hasPlatform($platforms, Platform::TikTok)
            && $this->hasText($this->tiktok_location_id)
            && ! $this->hasText($this->tiktok_location_name)
        ) {
            throw new InvalidArgumentException('tiktok_location_name is required when tiktok_location_id is set.');
        }

        if ($this->hasPlatform($platforms, Platform::X)) {
            if ($this->exclude_reply_user_ids !== [] && ! $this->hasText($this->reply_to_id)) {
                throw new InvalidArgumentException('exclude_reply_user_ids requires reply_to_id.');
            }

            if (count($this->tagged_user_ids) > 10) {
                throw new InvalidArgumentException('tagged_user_ids cannot contain more than 10 users.');
            }
        }

        if ($this->hasPlatform($platforms, Platform::Threads) && $this->hasText($this->threads_topic_tag)) {
            $topicTag = trim((string) $this->threads_topic_tag);

            if (mb_strlen($topicTag) > 50 || str_contains($topicTag, '.') || str_contains($topicTag, '&')) {
                throw new InvalidArgumentException('threads_topic_tag must be 50 characters or fewer and cannot contain periods or ampersands.');
            }
        }

        if ($this->hasPlatform($platforms, Platform::Threads)
            && $this->hasText($this->threads_reply_control)
            && ! in_array(trim((string) $this->threads_reply_control), [
                'everyone',
                'accounts_you_follow',
                'mentioned_only',
                'parent_post_author_only',
                'followers_only',
                'followers',
            ], true)) {
            throw new InvalidArgumentException('Invalid threads_reply_control value.');
        }

        if (! $this->hasPlatform($platforms, Platform::GoogleBusiness)) {
            return;
        }

        $ctaType = self::enumValue($this->gbp_cta_type);

        if (is_string($ctaType) && trim($ctaType) !== '') {
            $ctaType = strtoupper(trim($ctaType));

            if (GoogleBusinessCtaType::tryFrom($ctaType) === null) {
                throw new InvalidArgumentException("Invalid Google Business CTA type: {$ctaType}.");
            }

            if ($ctaType === GoogleBusinessCtaType::Call->value) {
                if ($this->gbp_cta_url !== null) {
                    throw new InvalidArgumentException('gbp_cta_url must not be set when gbp_cta_type is CALL.');
                }
            } elseif (! $this->hasText($this->gbp_cta_url)) {
                throw new InvalidArgumentException('gbp_cta_url is required when gbp_cta_type is set.');
            }
        }

        $topicType = self::enumValue($this->gbp_topic_type);

        if (is_string($topicType) && trim($topicType) !== '') {
            $topicType = strtoupper(trim($topicType));

            if (GoogleBusinessTopicType::tryFrom($topicType) === null) {
                throw new InvalidArgumentException("Invalid Google Business topic type: {$topicType}.");
            }
        }

        $mediaCategory = self::enumValue($this->gbp_media_category);

        if (is_string($mediaCategory) && trim($mediaCategory) !== '') {
            $mediaCategory = strtoupper(trim($mediaCategory));

            if (GoogleBusinessMediaCategory::tryFrom($mediaCategory) === null) {
                throw new InvalidArgumentException("Invalid Google Business media category: {$mediaCategory}.");
            }
        }

        if ($topicType === GoogleBusinessTopicType::Event->value) {
            foreach (
                [
                    'gbp_event_title' => $this->gbp_event_title,
                    'gbp_event_start_date' => $this->gbp_event_start_date,
                    'gbp_event_end_date' => $this->gbp_event_end_date,
                ] as $field => $value
            ) {
                if (! $this->hasText($value)) {
                    throw new InvalidArgumentException("{$field} is required for Google Business event posts.");
                }
            }

            $this->validateGoogleBusinessEventDateTime();
        }

    }

    private function validateThreadsTextOptions(): void
    {
        if ($this->threads_poll_options !== []) {
            $count = count($this->threads_poll_options);

            if ($count < 2 || $count > 4) {
                throw new InvalidArgumentException('threads_poll_options must contain between 2 and 4 options.');
            }

            foreach ($this->threads_poll_options as $option) {
                $length = mb_strlen(trim($option));

                if ($length < 1 || $length > 25) {
                    throw new InvalidArgumentException('Each Threads poll option must be between 1 and 25 characters.');
                }
            }
        }

        if ($this->hasText($this->threads_link_attachment)
            && preg_match('/^https?:\/\//i', trim((string) $this->threads_link_attachment)) !== 1) {
            throw new InvalidArgumentException('threads_link_attachment must be an HTTP(S) URL.');
        }
    }

    private function validateGoogleBusinessEventDateTime(): void
    {
        foreach ([
            'gbp_event_start_date' => $this->gbp_event_start_date,
            'gbp_event_end_date' => $this->gbp_event_end_date,
        ] as $field => $value) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', trim((string) $value)) !== 1) {
                throw new InvalidArgumentException("{$field} must use YYYY-MM-DD format.");
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $value));
            $errors = DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new InvalidArgumentException("{$field} must be a valid calendar date.");
            }
        }

        foreach ([
            'gbp_event_start_time' => $this->gbp_event_start_time,
            'gbp_event_end_time' => $this->gbp_event_end_time,
        ] as $field => $value) {
            if ($value !== null && trim($value) !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', trim($value)) !== 1) {
                throw new InvalidArgumentException("{$field} must use HH:MM 24-hour format.");
            }
        }
    }

    private function validateLinkedinVisibility(string $uploadType): void
    {
        $visibility = self::enumValue($this->visibility);

        if (
            $visibility !== null
            && trim((string) $visibility) !== ''
            && LinkedinVisibility::tryFrom((string) $visibility) === null
        ) {
            throw new InvalidArgumentException(
                "visibility must be one of PUBLIC, CONNECTIONS, LOGGED_IN, or CONTAINER for LinkedIn {$uploadType}."
            );
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private function validateMediaType(mixed $value, array $allowed, string $upload): void
    {
        $value = self::enumValue($value);
        if ($value !== null && ! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Invalid media type for {$upload} uploads.");
        }
    }

    private function hasText(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    private function validateLinkedinPoll(?string $link_url): void
    {
        $duration = self::enumValue($this->linkedin_poll_duration);

        if ($duration !== null) {
            $durationValue = is_string($duration) ? strtoupper(trim($duration)) : (string) $duration;

            if (! in_array($durationValue, ['ONE_DAY', 'THREE_DAYS', 'SEVEN_DAYS', 'FOURTEEN_DAYS', '1', '3', '7', '14'], true)) {
                throw new InvalidArgumentException('linkedin_poll_duration must be one of ONE_DAY, THREE_DAYS, SEVEN_DAYS, FOURTEEN_DAYS, 1, 3, 7, or 14.');
            }
        }

        $hasQuestion = $this->hasText($this->linkedin_poll_question);
        $hasOptions = $this->linkedin_poll_options !== [];

        if (! $hasQuestion && ! $hasOptions) {
            return;
        }

        if (! $hasQuestion) {
            throw new InvalidArgumentException('linkedin_poll_question is required when linkedin_poll_options is set.');
        }

        if (! $hasOptions) {
            throw new InvalidArgumentException('linkedin_poll_options is required when linkedin_poll_question is set.');
        }

        if (mb_strlen(trim((string) $this->linkedin_poll_question)) > 140) {
            throw new InvalidArgumentException('linkedin_poll_question must be 140 characters or fewer.');
        }

        if (count($this->linkedin_poll_options) < 2 || count($this->linkedin_poll_options) > 4) {
            throw new InvalidArgumentException('linkedin_poll_options must contain between 2 and 4 options.');
        }

        foreach ($this->linkedin_poll_options as $option) {
            if (mb_strlen($option) > 30) {
                throw new InvalidArgumentException('Each LinkedIn poll option must be 30 characters or fewer.');
            }
        }

        if ($this->hasText($this->linkedin_link_url) || $this->hasText($link_url)) {
            throw new InvalidArgumentException('LinkedIn polls cannot be combined with link previews.');
        }
    }

    private function validateFacebookCallToAction(?string $link_url): void
    {
        $hasCallToAction = is_array($this->facebook_call_to_action)
            ? $this->facebook_call_to_action !== []
            : $this->hasText($this->facebook_call_to_action);

        if (
            $hasCallToAction
            && ! $this->hasText($this->facebook_link_url)
            && ! $this->hasText($link_url)
        ) {
            throw new InvalidArgumentException('facebook_call_to_action requires facebook_link_url or link_url.');
        }
    }

    private function usesGoogleBusinessGallery(): bool
    {
        return $this->gbp_upload_to_gallery === true
            || in_array(strtoupper(trim((string) self::enumValue($this->gbp_post_type))), ['MEDIA', 'PHOTO', 'GALLERY'], true);
    }

    private function addTiktok(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('disable_comment', $this->disable_comment)
            ->field('brand_content_toggle', $this->brand_content_toggle)
            ->field('brand_organic_toggle', $this->brand_organic_toggle)
            ->field('privacy_level', self::enumValue($this->privacy_level))
            ->field('post_mode', self::enumValue($this->post_mode));

        if ($is_video) {
            $p->field('disable_duet', $this->disable_duet)
                ->field('disable_stitch', $this->disable_stitch)
                ->field('cover_timestamp', $this->cover_timestamp)
                ->field('is_aigc', $this->is_aigc);
        } else {
            $p->field('auto_add_music', $this->auto_add_music)
                ->field('photo_cover_index', $this->photo_cover_index);
        }

        return $p;
    }

    private function addInstagram(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('media_type', self::enumValue($this->media_type))
            ->field('collaborators', $this->collaborators)
            ->field('user_tags', $this->user_tags)
            ->field('location_id', $this->location_id);

        if ($is_video) {
            $p->field('share_mode', $this->share_mode)
                ->field('share_to_feed', $this->share_to_feed)
                ->field('cover_url', $this->cover_url)
                ->field('audio_name', $this->audio_name)
                ->field('thumb_offset', $this->thumb_offset);

            if ($this->cover_image !== null) {
                $p->media('cover_image', $this->cover_image instanceof Media ? $this->cover_image : Media::from($this->cover_image));
            }
        }

        return $p;
    }

    private function addYoutube(MultipartPayload $p): MultipartPayload
    {
        $p->field('tags[]', $this->tags)
            ->field('categoryId', $this->categoryId)
            ->field('privacyStatus', self::enumValue($this->privacyStatus))
            ->field('embeddable', $this->embeddable)
            ->field('license', $this->license)
            ->field('publicStatsViewable', $this->publicStatsViewable)
            ->field('thumbnail_url', $this->thumbnail_url)
            ->field('selfDeclaredMadeForKids', $this->selfDeclaredMadeForKids)
            ->field('containsSyntheticMedia', $this->containsSyntheticMedia)
            ->field('defaultLanguage', $this->defaultLanguage)
            ->field('defaultAudioLanguage', $this->defaultAudioLanguage)
            ->field('allowedCountries', $this->allowedCountries)
            ->field('blockedCountries', $this->blockedCountries)
            ->field('hasPaidProductPlacement', $this->hasPaidProductPlacement)
            ->field('recordingDate', $this->recordingDate)
            ->field('youtube_playlist_id', $this->youtube_playlist_id);

        if ($this->thumbnail !== null) {
            $p->media('thumbnail', $this->thumbnail instanceof Media ? $this->thumbnail : Media::from($this->thumbnail));
        }

        foreach ($this->youtube_subtitles as $idx => $subtitle) {
            $subtitle->addTo($p, (int) $idx);
        }

        return $p;
    }

    private function addLinkedin(
        MultipartPayload $p,
        bool $is_text,
        ?string $link_url = null,
        bool $is_video = false
    ): MultipartPayload {
        $p->field('visibility', self::enumValue($this->visibility));

        $p->field('target_linkedin_page_id', $this->target_linkedin_page_id);

        if ($is_text) {
            $p->field('linkedin_link_url', $this->linkedin_link_url ?? $link_url);
        } elseif ($is_video) {
            $p->field('thumbnail_url', $this->thumbnail_url);

            if ($this->thumbnail !== null) {
                $p->media('thumbnail', $this->thumbnail instanceof Media ? $this->thumbnail : Media::from($this->thumbnail));
            }
        }

        return $p;
    }

    private function addFacebook(MultipartPayload $p, bool $is_video, bool $is_text): MultipartPayload
    {
        $p->field('facebook_page_id', $this->facebook_page_id);

        if (! $is_text) {
            $p->field('facebook_media_type', self::enumValue($this->facebook_media_type));
        }

        if ($is_video) {
            $p->field('video_state', self::enumValue($this->video_state))
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

    private function addX(MultipartPayload $p, bool $is_text, bool $is_photo): MultipartPayload
    {
        $reply = self::enumValue($this->reply_settings);

        if ($reply === 'everyone') {
            $reply = null;
        }

        $p->field('reply_settings', $reply)
            ->field('nullcast', $this->nullcast)
            ->field('geo_place_id', $this->geo_place_id)
            ->field('for_super_followers_only', $this->for_super_followers_only)
            ->field('community_id', $this->community_id)
            ->field('share_with_followers', $this->share_with_followers)
            ->field('direct_message_deep_link', $this->direct_message_deep_link)
            ->field('x_long_text_as_post', $this->x_long_text_as_post)
            ->field('reply_to_id', $this->reply_to_id)
            ->field('exclude_reply_user_ids[]', $this->exclude_reply_user_ids);

        if ($is_text) {
            $p->field('quote_tweet_id', $this->quote_tweet_id)
                ->field('card_uri', $this->card_uri)
                ->field('poll_options[]', $this->poll_options)
                ->field('poll_duration', $this->poll_duration)
                ->field('poll_reply_settings', self::enumValue($this->poll_reply_settings));
        } else {
            $p->field('tagged_user_ids[]', $this->tagged_user_ids);

            if ($is_photo) {
                $p->field('x_thread_image_layout', $this->x_thread_image_layout);
            }
        }

        return $p;
    }

    private function addThreads(MultipartPayload $p, bool $is_text, bool $is_photo): MultipartPayload
    {
        if ($is_text) {
            $p->field('threads_long_text_as_post', $this->threads_long_text_as_post);
        }

        if ($is_photo) {
            $p->field('threads_thread_media_layout', $this->threads_thread_media_layout);
        }

        return $p->field('threads_topic_tag', $this->threads_topic_tag);
    }

    private function addBluesky(MultipartPayload $p, ?string $link_url = null, bool $include_reply_to = true): MultipartPayload
    {
        $p->field('bluesky_link_url', $this->bluesky_link_url ?? $link_url);

        if ($include_reply_to) {
            $p->field('reply_to_id', $this->reply_to_id);
        }

        return $p;
    }

    private function addGoogleBusiness(MultipartPayload $p): MultipartPayload
    {
        return $p->field('gbp_location_id', $this->gbp_location_id)
            ->field('gbp_language_code', $this->gbp_language_code)
            ->field('gbp_topic_type', $this->googleBusinessEnumValue($this->gbp_topic_type))
            ->field('gbp_cta_type', $this->googleBusinessEnumValue($this->gbp_cta_type))
            ->field('gbp_cta_url', $this->gbp_cta_url)
            ->field('gbp_post_type', $this->googleBusinessEnumValue($this->gbp_post_type))
            ->field('gbp_upload_to_gallery', $this->gbp_upload_to_gallery)
            ->field('gbp_media_category', $this->googleBusinessEnumValue($this->gbp_media_category))
            ->field('gbp_event_title', $this->gbp_event_title)
            ->field('gbp_event_start_date', $this->gbp_event_start_date)
            ->field('gbp_event_start_time', $this->gbp_event_start_time)
            ->field('gbp_event_end_date', $this->gbp_event_end_date)
            ->field('gbp_event_end_time', $this->gbp_event_end_time)
            ->field('gbp_coupon_code', $this->gbp_coupon_code)
            ->field('gbp_redeem_url', $this->gbp_redeem_url)
            ->field('gbp_terms', $this->gbp_terms);
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    private function hasPlatform(array $platforms, Platform $platform): bool
    {
        return in_array($platform->value, self::platformsToValues($platforms), true);
    }
}
