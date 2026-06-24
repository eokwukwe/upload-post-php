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
     * @param  list<string>  $tags
     * @param  list<YoutubeSubtitleData>  $youtube_subtitles
     * @param  list<string>  $tagged_user_ids
     * @param  list<string>  $poll_options
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

        public InstagramMediaType|string|null $media_type = null,
        public ?string $collaborators = null,
        public ?string $user_tags = null,
        public ?string $location_id = null,
        public ?bool $share_to_feed = null,
        public ?string $cover_url = null,
        public string|object|null $cover_image = null,
        public ?string $audio_name = null,
        public ?string $thumb_offset = null,

        public array $tags = [],
        public ?string $categoryId = null,
        public YoutubePrivacyStatus|string|null $privacyStatus = null,
        public ?bool $embeddable = null,
        public ?string $license = null,
        public ?bool $publicStatsViewable = null,
        public ?string $thumbnail_url = null,
        public ?bool $selfDeclaredMadeForKids = null,
        public ?bool $containsSyntheticMedia = null,
        public ?string $defaultLanguage = null,
        public ?string $defaultAudioLanguage = null,
        public ?string $allowedCountries = null,
        public ?string $blockedCountries = null,
        public ?bool $hasPaidProductPlacement = null,
        public ?string $recordingDate = null,
        public array $youtube_subtitles = [],

        public LinkedinVisibility|string|null $visibility = null,
        public ?string $target_linkedin_page_id = null,
        public ?string $linkedin_link_url = null,

        public ?string $facebook_page_id = null,
        public FacebookVideoState|string|null $video_state = null,
        public FacebookMediaType|string|null $facebook_media_type = null,
        public ?string $facebook_link_url = null,

        public ?string $pinterest_board_id = null,
        public ?string $pinterest_alt_text = null,
        public ?string $pinterest_link = null,
        public ?string $pinterest_cover_image_url = null,
        public ?string $pinterest_cover_image_content_type = null,
        public ?string $pinterest_cover_image_data = null,
        public int|string|null $pinterest_cover_image_key_frame_time = null,

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
        public ?string $place_id = null,
        public ?string $x_thread_image_layout = null,
        public ?string $post_url = null,
        public ?string $card_uri = null,
        public array $poll_options = [],
        public int|string|null $poll_duration = null,
        public XReplySettings|string|null $poll_reply_settings = null,

        public ?bool $threads_long_text_as_post = null,
        public ?string $threads_thread_media_layout = null,
        public ?string $threads_topic_tag = null,

        public ?string $subreddit = null,
        public ?string $flair_id = null,
        public ?string $reddit_link_url = null,

        public ?string $bluesky_link_url = null,
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
            media_type: self::stringOrNull($data['media_type'] ?? null),
            collaborators: self::stringOrNull($data['collaborators'] ?? null),
            user_tags: self::stringOrNull($data['user_tags'] ?? null),
            location_id: self::stringOrNull($data['location_id'] ?? null),
            share_to_feed: self::boolOrNull($data['share_to_feed'] ?? null),
            cover_url: self::stringOrNull($data['cover_url'] ?? null),
            cover_image: self::mediaInputOrNull($data['cover_image'] ?? null),
            audio_name: self::stringOrNull($data['audio_name'] ?? null),
            thumb_offset: self::stringOrNull($data['thumb_offset'] ?? null),
            tags: self::stringListFrom($data['tags'] ?? []),
            categoryId: self::stringOrNull($data['categoryId'] ?? null),
            privacyStatus: self::stringOrNull($data['privacyStatus'] ?? null),
            embeddable: self::boolOrNull($data['embeddable'] ?? null),
            license: self::stringOrNull($data['license'] ?? null),
            publicStatsViewable: self::boolOrNull($data['publicStatsViewable'] ?? null),
            thumbnail_url: self::stringOrNull($data['thumbnail_url'] ?? null),
            selfDeclaredMadeForKids: self::boolOrNull($data['selfDeclaredMadeForKids'] ?? null),
            containsSyntheticMedia: self::boolOrNull($data['containsSyntheticMedia'] ?? null),
            defaultLanguage: self::stringOrNull($data['defaultLanguage'] ?? null),
            defaultAudioLanguage: self::stringOrNull($data['defaultAudioLanguage'] ?? null),
            allowedCountries: self::stringOrNull($data['allowedCountries'] ?? null),
            blockedCountries: self::stringOrNull($data['blockedCountries'] ?? null),
            hasPaidProductPlacement: self::boolOrNull($data['hasPaidProductPlacement'] ?? null),
            recordingDate: self::stringOrNull($data['recordingDate'] ?? null),
            youtube_subtitles: self::youtubeSubtitlesFrom($data['youtube_subtitles'] ?? []),
            visibility: self::stringOrNull($data['visibility'] ?? null),
            target_linkedin_page_id: self::stringOrNull($data['target_linkedin_page_id'] ?? null),
            linkedin_link_url: self::stringOrNull($data['linkedin_link_url'] ?? null),
            facebook_page_id: self::stringOrNull($data['facebook_page_id'] ?? null),
            video_state: self::stringOrNull($data['video_state'] ?? null),
            facebook_media_type: self::stringOrNull($data['facebook_media_type'] ?? null),
            facebook_link_url: self::stringOrNull($data['facebook_link_url'] ?? null),
            pinterest_board_id: self::stringOrNull($data['pinterest_board_id'] ?? null),
            pinterest_alt_text: self::stringOrNull($data['pinterest_alt_text'] ?? null),
            pinterest_link: self::stringOrNull($data['pinterest_link'] ?? null),
            pinterest_cover_image_url: self::stringOrNull($data['pinterest_cover_image_url'] ?? null),
            pinterest_cover_image_content_type: self::stringOrNull($data['pinterest_cover_image_content_type'] ?? null),
            pinterest_cover_image_data: self::stringOrNull($data['pinterest_cover_image_data'] ?? null),
            pinterest_cover_image_key_frame_time: self::intStringOrNull($data['pinterest_cover_image_key_frame_time'] ?? null),
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
            place_id: self::stringOrNull($data['place_id'] ?? null),
            x_thread_image_layout: self::stringOrNull($data['x_thread_image_layout'] ?? null),
            post_url: self::stringOrNull($data['post_url'] ?? null),
            card_uri: self::stringOrNull($data['card_uri'] ?? null),
            poll_options: self::stringListFrom($data['poll_options'] ?? []),
            poll_duration: self::intStringOrNull($data['poll_duration'] ?? null),
            poll_reply_settings: self::stringOrNull($data['poll_reply_settings'] ?? null),
            threads_long_text_as_post: self::boolOrNull($data['threads_long_text_as_post'] ?? null),
            threads_thread_media_layout: self::stringOrNull($data['threads_thread_media_layout'] ?? null),
            threads_topic_tag: self::stringOrNull($data['threads_topic_tag'] ?? null),
            subreddit: self::stringOrNull($data['subreddit'] ?? null),
            flair_id: self::stringOrNull($data['flair_id'] ?? null),
            reddit_link_url: self::stringOrNull($data['reddit_link_url'] ?? null),
            bluesky_link_url: self::stringOrNull($data['bluesky_link_url'] ?? null),
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
            'media_type' => self::enumValue($this->media_type),
            'collaborators' => $this->collaborators,
            'user_tags' => $this->user_tags,
            'location_id' => $this->location_id,
            'share_to_feed' => $this->share_to_feed,
            'cover_url' => $this->cover_url,
            'cover_image' => $this->cover_image,
            'audio_name' => $this->audio_name,
            'thumb_offset' => $this->thumb_offset,
            'tags' => $this->tags,
            'categoryId' => $this->categoryId,
            'privacyStatus' => self::enumValue($this->privacyStatus),
            'embeddable' => $this->embeddable,
            'license' => $this->license,
            'publicStatsViewable' => $this->publicStatsViewable,
            'thumbnail_url' => $this->thumbnail_url,
            'selfDeclaredMadeForKids' => $this->selfDeclaredMadeForKids,
            'containsSyntheticMedia' => $this->containsSyntheticMedia,
            'defaultLanguage' => $this->defaultLanguage,
            'defaultAudioLanguage' => $this->defaultAudioLanguage,
            'allowedCountries' => $this->allowedCountries,
            'blockedCountries' => $this->blockedCountries,
            'hasPaidProductPlacement' => $this->hasPaidProductPlacement,
            'recordingDate' => $this->recordingDate,
            'youtube_subtitles' => array_map(
                static fn (YoutubeSubtitleData $subtitle): array => $subtitle->toArray(),
                $this->youtube_subtitles
            ),
            'visibility' => self::enumValue($this->visibility),
            'target_linkedin_page_id' => $this->target_linkedin_page_id,
            'linkedin_link_url' => $this->linkedin_link_url,
            'facebook_page_id' => $this->facebook_page_id,
            'video_state' => self::enumValue($this->video_state),
            'facebook_media_type' => self::enumValue($this->facebook_media_type),
            'facebook_link_url' => $this->facebook_link_url,
            'pinterest_board_id' => $this->pinterest_board_id,
            'pinterest_alt_text' => $this->pinterest_alt_text,
            'pinterest_link' => $this->pinterest_link,
            'pinterest_cover_image_url' => $this->pinterest_cover_image_url,
            'pinterest_cover_image_content_type' => $this->pinterest_cover_image_content_type,
            'pinterest_cover_image_data' => $this->pinterest_cover_image_data,
            'pinterest_cover_image_key_frame_time' => $this->pinterest_cover_image_key_frame_time,
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
            'place_id' => $this->place_id,
            'x_thread_image_layout' => $this->x_thread_image_layout,
            'post_url' => $this->post_url,
            'card_uri' => $this->card_uri,
            'poll_options' => $this->poll_options,
            'poll_duration' => $this->poll_duration,
            'poll_reply_settings' => self::enumValue($this->poll_reply_settings),
            'threads_long_text_as_post' => $this->threads_long_text_as_post,
            'threads_thread_media_layout' => $this->threads_thread_media_layout,
            'threads_topic_tag' => $this->threads_topic_tag,
            'subreddit' => $this->subreddit,
            'flair_id' => $this->flair_id,
            'reddit_link_url' => $this->reddit_link_url,
            'bluesky_link_url' => $this->bluesky_link_url,
        ]);
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

    private function addTiktok(MultipartPayload $p, bool $is_video): MultipartPayload
    {
        $p->field('disable_comment', $this->disable_comment)
            ->field('brand_content_toggle', $this->brand_content_toggle)
            ->field('brand_organic_toggle', $this->brand_organic_toggle);
        if ($is_video) {
            $p->field('privacy_level', self::enumValue($this->privacy_level))
                ->field('disable_duet', $this->disable_duet)
                ->field('disable_stitch', $this->disable_stitch)
                ->field('cover_timestamp', $this->cover_timestamp)
                ->field('is_aigc', $this->is_aigc)
                ->field('post_mode', self::enumValue($this->post_mode));
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
            $p->field('share_to_feed', $this->share_to_feed)
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
            ->field('recordingDate', $this->recordingDate);
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
        $p->field('visibility', self::enumValue($this->visibility))
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
            $p->field('video_state', self::enumValue($this->video_state))
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
        $reply = self::enumValue($this->reply_settings);
        if ($reply === 'everyone') {
            $reply = null;
        }
        $p->field('reply_settings', $reply)
            ->field('nullcast', $this->nullcast)
            ->field('quote_tweet_id', $this->quote_tweet_id)
            ->field('geo_place_id', $this->geo_place_id)
            ->field('for_super_followers_only', $this->for_super_followers_only)
            ->field('community_id', $this->community_id)
            ->field('share_with_followers', $this->share_with_followers)
            ->field('direct_message_deep_link', $this->direct_message_deep_link)
            ->field('x_long_text_as_post', $this->x_long_text_as_post);
        if ($is_text) {
            $p->field('post_url', $this->post_url)
                ->field('card_uri', $this->card_uri)
                ->field('poll_options[]', $this->poll_options)
                ->field('poll_duration', $this->poll_duration)
                ->field('poll_reply_settings', self::enumValue($this->poll_reply_settings));
        } else {
            $p->field('tagged_user_ids[]', $this->tagged_user_ids)
                ->field('place_id', $this->place_id)
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
        $p->field('subreddit', $this->subreddit)->field('flair_id', $this->flair_id);
        if ($is_text) {
            $p->field('reddit_link_url', $this->reddit_link_url ?? $link_url);
        }

        return $p;
    }

    /**
     * @param  list<Platform|string>  $platforms
     */
    private function hasPlatform(array $platforms, Platform $platform): bool
    {
        return in_array($platform->value, self::platformsToValues($platforms), true);
    }
}
