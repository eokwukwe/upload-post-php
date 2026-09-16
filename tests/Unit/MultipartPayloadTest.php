<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
use Softgeng\UploadPost\Enums\FacebookMediaType;
use Softgeng\UploadPost\Enums\Platform;
use Softgeng\UploadPost\Support\MultipartPayload;

test('multipart payload converts arrays booleans and enums', function (): void {
    $parts = (new MultipartPayload)
        ->field('platform[]', [Platform::TikTok, Platform::YouTube])
        ->field('async_upload', true)
        ->field('empty', null)
        ->all();

    expect($parts)->toHaveCount(3)
        ->and($parts[0]['contents'])->toBe('tiktok')
        ->and($parts[1]['contents'])->toBe('youtube')
        ->and($parts[2]['contents'])->toBe('true');
});

test('upload text only includes options for selected platforms', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::X], title: 'Hello'),
        options: new PlatformOptions(
            facebook_page_id: 'facebook-page',
            quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)
        ->toContain('quote_tweet_id')
        ->not->toContain('facebook_page_id');
});

test('upload text keeps options for selected platforms', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Facebook], title: 'Hello'),
        options: new PlatformOptions(
            facebook_page_id: 'facebook-page',
            quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)
        ->toContain('facebook_page_id')
        ->not->toContain('quote_tweet_id');
});

test('upload video builds node-compatible multipart fields for selected platforms', function (): void {
    $parts = (new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: new CommonUploadData(user: 'profile', platforms: [Platform::TikTok, Platform::YouTube], title: 'Video'),
        options: new PlatformOptions(
            disable_comment: true,
            tags: ['sdk'],
            facebook_page_id: 'facebook-page',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($contents['video'])->toBe('https://example.com/video.mp4')
        ->and($names)->toContain('platform[]', 'disable_comment', 'tags[]')
        ->and($names)->not->toContain('facebook_page_id');
});

test('upload photos builds node-compatible multipart fields for selected platforms', function (): void {
    $parts = (new UploadPhotosData(
        photos: ['https://example.com/photo.jpg'],
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Instagram, Platform::Pinterest], title: 'Photos'),
        options: new PlatformOptions(
            media_type: 'IMAGE',
            pinterest_board_id: 'board-123',
            quote_tweet_id: 'tweet-123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($contents['photos[]'])->toBe('https://example.com/photo.jpg')
        ->and($names)->toContain('platform[]', 'media_type', 'pinterest_board_id')
        ->and($names)->not->toContain('quote_tweet_id');
});

test('pinterest ai disclosures are sent as repeated scoped fields', function (): void {
    $parts = (new UploadPhotosData(
        photos: ['https://example.com/photo.jpg'],
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Pinterest], title: 'Photos'),
        options: new PlatformOptions(
            pinterest_board_id: 'board-123',
            pinterest_ai_disclosures: ['AI_MODIFIED', 'SYNTHETIC_PERFORMER'],
        ),
    ))->toMultipart()->all();

    $disclosures = array_values(array_filter(
        $parts,
        static fn (array $part): bool => $part['name'] === 'pinterest_ai_disclosures[]',
    ));

    expect($disclosures)->toHaveCount(2)
        ->and(array_column($disclosures, 'contents'))->toBe(['AI_MODIFIED', 'SYNTHETIC_PERFORMER']);
});

test('upload document forces linkedin multipart fields', function (): void {
    $parts = (new UploadDocumentData(
        document: 'https://example.com/deck.pdf',
        user: 'profile',
        title: 'Document',
        options: new PlatformOptions(
            visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
        ),
        first_comment: 'General first comment',
        linkedin_first_comment: 'LinkedIn first comment',
    ))->toMultipart()->all();

    $contents = array_column($parts, 'contents', 'name');

    expect($contents['document'])->toBe('https://example.com/deck.pdf')
        ->and($contents['platform[]'])->toBe('linkedin')
        ->and($contents['visibility'])->toBe('PUBLIC')
        ->and($contents['first_comment'])->toBe('General first comment')
        ->and($contents['linkedin_first_comment'])->toBe('LinkedIn first comment')
        ->and($contents)->not->toHaveKey('facebook_page_id');
});

test('linkedin thumbnails are limited to video uploads', function (): void {
    $photoParts = (new UploadPhotosData(
        photos: [__FILE__],
        common: new CommonUploadData(user: 'profile', platforms: [Platform::LinkedIn], title: 'Photo'),
        options: new PlatformOptions(thumbnail: __FILE__, thumbnail_url: 'https://example.com/thumb.jpg'),
    ))->toMultipart()->all();

    $documentParts = (new UploadDocumentData(
        document: __FILE__,
        user: 'profile',
        title: 'Document',
        options: new PlatformOptions(thumbnail: __FILE__, thumbnail_url: 'https://example.com/thumb.jpg'),
    ))->toMultipart()->all();

    expect(array_column($photoParts, 'name'))->not->toContain('thumbnail', 'thumbnail_url')
        ->and(array_column($documentParts, 'name'))->not->toContain('thumbnail', 'thumbnail_url');
});

test('video options include selected platform-specific fields', function (): void {
    $parts = (new UploadVideoData(
        video: 'https://example.com/video.mp4',
        common: new CommonUploadData(
            user: 'profile',
            platforms: [
                Platform::TikTok,
                Platform::Instagram,
                Platform::YouTube,
                Platform::LinkedIn,
                Platform::Facebook,
                Platform::Pinterest,
                Platform::X,
                Platform::Threads,
                Platform::GoogleBusiness,
            ],
            title: 'Video',
        ),
        options: new PlatformOptions(
            disable_comment: true,
            privacy_level: 'PUBLIC_TO_EVERYONE',
            disable_duet: true,
            disable_stitch: true,
            cover_timestamp: 3,
            is_aigc: true,
            post_mode: 'DIRECT_POST',
            media_type: 'REELS',
            collaborators: 'user',
            user_tags: 'tag',
            location_id: 'location',
            share_mode: 'CUSTOM',
            share_to_feed: true,
            cover_url: 'https://example.com/cover.jpg',
            cover_image: new SplFileInfo(__FILE__),
            audio_name: 'audio',
            thumb_offset: '1',
            tags: ['sdk'],
            categoryId: '22',
            privacyStatus: 'public',
            embeddable: true,
            license: 'youtube',
            publicStatsViewable: true,
            thumbnail: 'https://example.com/youtube-thumbnail.jpg',
            thumbnail_url: 'https://example.com/thumb.jpg',
            selfDeclaredMadeForKids: false,
            containsSyntheticMedia: true,
            defaultLanguage: 'en',
            defaultAudioLanguage: 'en',
            allowedCountries: 'US',
            hasPaidProductPlacement: true,
            recordingDate: '2026-01-01',
            youtube_playlist_id: 'playlist-123',
            youtube_subtitles: [new YoutubeSubtitleData(language: 'en', file: __FILE__)],
            visibility: 'PUBLIC',
            target_linkedin_page_id: 'linkedin-page',
            linkedin_subtitles_url: 'https://example.com/linkedin-subtitles.srt',
            linkedin_subtitles_text: '1\n00:00:00,000 --> 00:00:01,000\nHello',
            facebook_page_id: 'facebook-page',
            video_state: 'PUBLISHED',
            facebook_media_type: 'REELS',
            pinterest_board_id: 'board',
            pinterest_alt_text: 'Alt',
            pinterest_link: 'https://example.com',
            pinterest_cover_image_url: 'https://example.com/pinterest-cover.jpg',
            pinterest_cover_image_content_type: 'image/jpeg',
            pinterest_cover_image_data: 'base64',
            pinterest_cover_image_key_frame_time: 5,
            reply_settings: 'following',
            nullcast: true,
            geo_place_id: 'geo',
            for_super_followers_only: true,
            community_id: 'community',
            share_with_followers: true,
            direct_message_deep_link: 'https://x.example.com/dm',
            x_long_text_as_post: true,
            tagged_user_ids: ['user-1'],
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            x_thread_image_layout: 'grid',
            threads_long_text_as_post: true,
            threads_thread_media_layout: 'carousel',
            threads_topic_tag: 'php',
            gbp_location_id: 'locations/123',
            gbp_topic_type: 'OFFER',
            gbp_cta_type: 'SHOP',
            gbp_cta_url: 'https://example.com/shop',
            gbp_event_title: 'Launch',
            gbp_event_start_date: '2026-08-01',
            gbp_event_start_time: '09:00',
            gbp_event_end_date: '2026-08-02',
            gbp_event_end_time: '17:00',
            gbp_coupon_code: 'SAVE20',
            gbp_redeem_url: 'https://example.com/redeem',
            gbp_terms: 'Terms apply.',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');
    $filenames = array_column($parts, 'filename', 'name');

    expect($names)->toContain(
        'privacy_level',
        'share_mode',
        'cover_url',
        'cover_image',
        'thumbnail',
        'linkedin_subtitles_url',
        'linkedin_subtitles_text',
        'youtube_playlist_id',
        'youtube_subtitle_file_0',
        'visibility',
        'facebook_page_id',
        'pinterest_cover_image_url',
        'tagged_user_ids[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'threads_topic_tag',
        'gbp_terms',
    )->and($contents['thumbnail'])->toBe('https://example.com/youtube-thumbnail.jpg')
        ->and($filenames['cover_image'])->toBe(basename(__FILE__))
        ->and($names)->not->toContain(
            'quote_tweet_id',
            'x_thread_image_layout',
            'threads_long_text_as_post',
            'threads_thread_media_layout',
        );
});

test('photo options include selected platform-specific fields', function (): void {
    $parts = (new UploadPhotosData(
        photos: ['https://example.com/photo.jpg'],
        common: new CommonUploadData(
            user: 'profile',
            platforms: [
                Platform::TikTok,
                Platform::Instagram,
                Platform::LinkedIn,
                Platform::Facebook,
                Platform::Pinterest,
                Platform::X,
                Platform::Threads,
                Platform::GoogleBusiness,
            ],
            title: 'Photos',
        ),
        options: new PlatformOptions(
            privacy_level: 'SELF_ONLY',
            post_mode: 'MEDIA_UPLOAD',
            auto_add_music: true,
            photo_cover_index: 1,
            media_type: 'IMAGE',
            visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
            facebook_media_type: FacebookMediaType::Posts,
            pinterest_board_id: 'board',
            reply_settings: 'everyone',
            tagged_user_ids: ['user-1'],
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            x_thread_image_layout: '4',
            threads_long_text_as_post: true,
            threads_thread_media_layout: '5,5',
            threads_topic_tag: 'php',
            gbp_location_id: 'locations/123',
            gbp_post_type: 'MEDIA',
            gbp_upload_to_gallery: true,
            gbp_media_category: 'EXTERIOR',
            gbp_language_code: 'en-GB',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($names)->toContain(
        'privacy_level',
        'post_mode',
        'auto_add_music',
        'media_type',
        'visibility',
        'facebook_page_id',
        'facebook_media_type',
        'pinterest_board_id',
        'tagged_user_ids[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'x_thread_image_layout',
        'threads_thread_media_layout',
        'threads_topic_tag',
        'gbp_location_id',
        'gbp_post_type',
        'gbp_upload_to_gallery',
        'gbp_media_category',
        'gbp_language_code',
    )->and($contents['privacy_level'])->toBe('SELF_ONLY')
        ->and($contents['post_mode'])->toBe('MEDIA_UPLOAD')
        ->and($contents['facebook_media_type'])->toBe('POSTS')
        ->and($contents['gbp_upload_to_gallery'])->toBe('true')
        ->and($contents['gbp_media_category'])->toBe('EXTERIOR')
        ->and($contents['gbp_language_code'])->toBe('en-GB')
        ->and(array_count_values($names)['gbp_language_code'])->toBe(1)
        ->and($names)->not->toContain('reply_settings', 'quote_tweet_id', 'threads_long_text_as_post');
});

test('text options include selected platform-specific fields', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::LinkedIn, Platform::Facebook, Platform::X, Platform::Threads, Platform::Bluesky, Platform::GoogleBusiness],
            title: 'Text',
        ),
        link_url: 'https://example.com',
        options: new PlatformOptions(
            visibility: 'PUBLIC',
            facebook_link_url: 'https://facebook.example.com',
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            poll_options: ['yes', 'no'],
            poll_duration: 60,
            poll_reply_settings: 'following',
            threads_long_text_as_post: true,
            threads_thread_media_layout: '5,5',
            threads_topic_tag: 'php',
            bluesky_link_url: 'https://bluesky.example.com',
            gbp_topic_type: 'STANDARD',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($names)->toContain(
        'linkedin_link_url',
        'facebook_link_url',
        'poll_options[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'threads_long_text_as_post',
        'threads_topic_tag',
        'bluesky_link_url',
        'gbp_topic_type',
    )->and($contents['bluesky_link_url'])->toBe('https://bluesky.example.com')
        ->and($names)->not->toContain('visibility', 'threads_thread_media_layout');
});

test('bluesky text options include replies without duplicating x fields', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(user: 'profile', platforms: [Platform::Bluesky], title: 'Reply'),
        options: new PlatformOptions(reply_to_id: 'at://did:plc:123/app.bsky.feed.post/456'),
    ))->toMultipart()->all();

    expect(array_column($parts, 'contents', 'name')['reply_to_id'])
        ->toBe('at://did:plc:123/app.bsky.feed.post/456');
});

test('common upload fields include request id and messaging platform titles', function (): void {
    $payload = new MultipartPayload;

    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::Discord, Platform::Telegram],
        request_id: 'request-123',
        discord_title: 'Discord caption',
        telegram_title: 'Telegram caption',
    ))->addForTextTo($payload);

    $contents = array_column($payload->all(), 'contents', 'name');

    expect($contents['request_id'])->toBe('request-123')
        ->and($contents['discord_title'])->toBe('Discord caption')
        ->and($contents['telegram_title'])->toBe('Telegram caption');
});

test('common upload fields are scoped to the endpoint and selected platforms', function (): void {
    $textPayload = new MultipartPayload;
    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::X],
        title: 'Post',
        first_comment: 'General comment',
        x_title: 'X post',
        youtube_title: 'YouTube post',
        x_first_comment: 'X comment',
        youtube_first_comment: 'YouTube comment',
    ))->addForTextTo($textPayload);

    $textNames = array_column($textPayload->all(), 'name');

    expect($textNames)->toContain('first_comment', 'x_title', 'x_first_comment')
        ->not->toContain('youtube_title', 'youtube_first_comment');

    $photoPayload = new MultipartPayload;
    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::YouTube],
        title: 'Post',
        first_comment: 'General comment',
        youtube_title: 'YouTube post',
        youtube_description: 'YouTube description',
        youtube_first_comment: 'YouTube comment',
    ))->addForPhotosTo($photoPayload);

    $photoNames = array_column($photoPayload->all(), 'name');

    expect($photoNames)->not->toContain(
        'first_comment',
        'youtube_title',
        'youtube_description',
        'youtube_first_comment',
    );
});

test('facebook photo descriptions are included for Facebook uploads', function (): void {
    $payload = new MultipartPayload;

    (new CommonUploadData(
        user: 'profile',
        platforms: [Platform::Facebook],
        title: 'Photo',
        facebook_description: 'Facebook photo description',
    ))->addForPhotosTo($payload);

    expect(array_column($payload->all(), 'contents', 'name')['facebook_description'])
        ->toBe('Facebook photo description');
});

test('TikTok video and photo uploads include generic and TikTok-specific first comments', function (): void {
    $data = CommonUploadData::fromArray([
        'user' => 'profile',
        'platforms' => ['tiktok'],
        'first_comment' => 'Generic TikTok comment',
        'tiktok_first_comment' => 'TikTok-specific comment',
    ]);

    $videoPayload = new MultipartPayload;
    $data->addForVideoTo($videoPayload);
    $photoPayload = new MultipartPayload;
    $data->addForPhotosTo($photoPayload);

    expect($data->tiktok_first_comment)->toBe('TikTok-specific comment')
        ->and($data->toArray())->toBe([
            'user' => 'profile',
            'platforms' => ['tiktok'],
            'first_comment' => 'Generic TikTok comment',
            'tiktok_first_comment' => 'TikTok-specific comment',
        ])
        ->and(array_column($videoPayload->all(), 'contents', 'name'))->toMatchArray([
            'first_comment' => 'Generic TikTok comment',
            'tiktok_first_comment' => 'TikTok-specific comment',
        ])
        ->and(array_column($photoPayload->all(), 'contents', 'name'))->toMatchArray([
            'first_comment' => 'Generic TikTok comment',
            'tiktok_first_comment' => 'TikTok-specific comment',
        ]);
});

test('explicit platform options serialize only for their documented platform and upload type', function (): void {
    $parts = (new UploadVideoData(
        video: __FILE__,
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::TikTok, Platform::X],
            title: 'Video',
            external_id: 'post-123',
            is_ai_generated: true,
        ),
        options: new PlatformOptions(
            tiktok_location_id: 'location-456',
            tiktok_location_name: 'Lekki Conservation Centre',
            tiktok_music_id: 'music-123',
            instagram_alt_text: 'must not be sent',
            x_paid_partnership: true,
        ),
    ))->toMultipart()->all();

    $contents = array_column($parts, 'contents', 'name');

    expect($contents['external_id'])->toBe('post-123')
        ->and($contents['is_ai_generated'])->toBe('true')
        ->and($contents['tiktok_music_id'])->toBe('music-123')
        ->and($contents['tiktok_location_id'])->toBe('location-456')
        ->and($contents['x_paid_partnership'])->toBe('true')
        ->and($contents)->not->toHaveKey('tiktok_music_start')
        ->and($contents)->not->toHaveKey('instagram_alt_text');
});

test('empty platform option values are omitted from multipart payloads', function (): void {
    $parts = (new UploadVideoData(
        video: __FILE__,
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::TikTok],
            title: 'Video',
        ),
        options: new PlatformOptions(tiktok_location_id: ''),
    ))->toMultipart()->all();

    expect(array_column($parts, 'name'))->not->toContain('tiktok_location_id');
});

test('TikTok cover images are uploaded as multipart media', function (): void {
    $parts = (new UploadVideoData(
        video: __FILE__,
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::TikTok],
            title: 'Video',
        ),
        options: new PlatformOptions(tiktok_cover_image: __FILE__),
    ))->toMultipart()->all();

    $cover = collect($parts)->firstWhere('name', 'tiktok_cover_image');

    expect($cover['filename'])->toBe(basename(__FILE__))
        ->and(is_resource($cover['contents']))->toBeTrue();
});

test('X video uploads support inline subtitles', function (): void {
    $parts = (new UploadVideoData(
        video: __FILE__,
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::X],
            title: 'Video',
        ),
        options: new PlatformOptions(
            x_subtitles: "1\n00:00:00,000 --> 00:00:01,000\nHello",
            x_subtitles_language: 'en',
            x_subtitles_name: 'English',
        ),
    ))->toMultipart()->all();

    expect(array_column($parts, 'contents', 'name'))->toMatchArray([
        'x_subtitles' => "1\n00:00:00,000 --> 00:00:01,000\nHello",
        'x_subtitles_language' => 'en',
        'x_subtitles_name' => 'English',
    ]);
});

test('explicit platform options preserve documented list, JSON, and media encodings', function (): void {
    $photoParts = (new UploadPhotosData(
        photos: [__FILE__],
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::Instagram, Platform::Facebook, Platform::Threads, Platform::Bluesky],
            title: 'Photos',
        ),
        options: new PlatformOptions(
            instagram_alt_text: ['Instagram image'],
            facebook_alt_text: ['Facebook image'],
            facebook_targeting: ['countries' => ['NG']],
            facebook_feed_targeting: ['interests' => ['technology']],
            threads_alt_text: ['Threads image'],
            bluesky_alt_text: ['Bluesky image'],
        ),
    ))->toMultipart()->all();

    $photoContents = array_column($photoParts, 'contents', 'name');
    $photoNames = array_column($photoParts, 'name');

    expect($photoContents['instagram_alt_text'])->toBe('["Instagram image"]')
        ->and($photoContents['facebook_targeting'])->toBe('{"countries":["NG"]}')
        ->and($photoContents['facebook_feed_targeting'])->toBe('{"interests":["technology"]}')
        ->and($photoContents['bluesky_alt_text'])->toBe('["Bluesky image"]')
        ->and($photoNames)->toContain('facebook_alt_text[]', 'threads_alt_text[]');

    $textParts = (new UploadTextData(
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::Facebook, Platform::X],
            title: 'Article',
        ),
        link_url: 'https://example.com/article',
        options: new PlatformOptions(
            facebook_call_to_action: ['type' => 'SHOP_NOW'],
            facebook_child_attachments: [['link' => 'https://example.com']],
            x_article_title: 'Long-form article',
            x_article_cover_media: __FILE__,
        ),
    ))->toMultipart()->all();

    $textContents = array_column($textParts, 'contents', 'name');
    $articleCover = collect($textParts)->firstWhere('name', 'x_article_cover_media');

    expect($textContents['facebook_call_to_action'])->toBe('{"type":"SHOP_NOW"}')
        ->and($textContents['facebook_child_attachments'])->toBe('[{"link":"https:\/\/example.com"}]')
        ->and($articleCover['filename'])->toBe(basename(__FILE__));
});

test('linkedin document uploads support scheduling', function (): void {
    $scheduledDate = new DateTimeImmutable('+30 days', new DateTimeZone('UTC'));

    $parts = (new UploadDocumentData(
        document: __FILE__,
        user: 'profile',
        title: 'Deck',
        scheduled_date: $scheduledDate,
        timezone: 'Africa/Lagos',
    ))->toMultipart()->all();

    expect(array_column($parts, 'contents', 'name'))->toMatchArray([
        'scheduled_date' => $scheduledDate->format(DateTimeInterface::ATOM),
        'timezone' => 'Africa/Lagos',
    ]);
});
