<?php

declare(strict_types=1);

use Softgeng\UploadPost\Data\CommonUploadData;
use Softgeng\UploadPost\Data\PlatformOptions;
use Softgeng\UploadPost\Data\UploadDocumentData;
use Softgeng\UploadPost\Data\UploadPhotosData;
use Softgeng\UploadPost\Data\UploadTextData;
use Softgeng\UploadPost\Data\UploadVideoData;
use Softgeng\UploadPost\Data\YoutubeSubtitleData;
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

test('upload document forces linkedin multipart fields', function (): void {
    $parts = (new UploadDocumentData(
        document: 'https://example.com/deck.pdf',
        user: 'profile',
        title: 'Document',
        options: new PlatformOptions(
            visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
        ),
    ))->toMultipart()->all();

    $contents = array_column($parts, 'contents', 'name');

    expect($contents['document'])->toBe('https://example.com/deck.pdf')
        ->and($contents['platform[]'])->toBe('linkedin')
        ->and($contents['visibility'])->toBe('PUBLIC')
        ->and($contents)->not->toHaveKey('facebook_page_id');
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
                Platform::Reddit,
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
            cover_image: 'https://example.com/cover-image.jpg',
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
            blockedCountries: 'CA',
            hasPaidProductPlacement: true,
            recordingDate: '2026-01-01',
            youtube_playlist_id: 'playlist-123',
            youtube_subtitles: [new YoutubeSubtitleData(language: 'en', url: 'https://example.com/subtitles.vtt')],
            visibility: 'PUBLIC',
            target_linkedin_page_id: 'linkedin-page',
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
            quote_tweet_id: 'quote',
            geo_place_id: 'geo',
            for_super_followers_only: true,
            community_id: 'community',
            share_with_followers: true,
            direct_message_deep_link: 'https://x.example.com/dm',
            x_long_text_as_post: true,
            tagged_user_ids: ['user-1'],
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            place_id: 'place',
            x_thread_image_layout: 'grid',
            threads_long_text_as_post: true,
            threads_thread_media_layout: 'carousel',
            threads_topic_tag: 'php',
            subreddit: 'php',
            flair_id: 'flair',
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

    expect($names)->toContain(
        'privacy_level',
        'share_mode',
        'cover_url',
        'cover_image',
        'thumbnail',
        'youtube_playlist_id',
        'youtube_subtitle_file_0',
        'visibility',
        'facebook_page_id',
        'pinterest_cover_image_url',
        'tagged_user_ids[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'place_id',
        'threads_topic_tag',
        'subreddit',
        'gbp_terms',
    )->and($contents['thumbnail'])->toBe('https://example.com/youtube-thumbnail.jpg');
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
                Platform::Reddit,
                Platform::GoogleBusiness,
            ],
            title: 'Photos',
        ),
        options: new PlatformOptions(
            auto_add_music: true,
            photo_cover_index: 1,
            media_type: 'IMAGE',
            visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
            pinterest_board_id: 'board',
            reply_settings: 'everyone',
            tagged_user_ids: ['user-1'],
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            threads_topic_tag: 'php',
            subreddit: 'php',
            flair_id: 'flair',
            gbp_location_id: 'locations/123',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)->toContain(
        'auto_add_music',
        'media_type',
        'visibility',
        'facebook_page_id',
        'pinterest_board_id',
        'tagged_user_ids[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'subreddit',
        'threads_topic_tag',
        'gbp_location_id',
    )->not->toContain('reply_settings');
});

test('text options include selected platform-specific fields', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::LinkedIn, Platform::Facebook, Platform::X, Platform::Threads, Platform::Reddit, Platform::Bluesky, Platform::GoogleBusiness],
            title: 'Text',
        ),
        link_url: 'https://example.com',
        options: new PlatformOptions(
            visibility: 'PUBLIC',
            facebook_link_url: 'https://facebook.example.com',
            post_url: 'https://x.example.com/post',
            card_uri: 'card',
            poll_options: ['yes', 'no'],
            poll_duration: 60,
            poll_reply_settings: 'following',
            reply_to_id: 'tweet-123',
            exclude_reply_user_ids: ['user-2'],
            threads_topic_tag: 'php',
            subreddit: 'php',
            reddit_link_url: 'https://reddit.example.com',
            bluesky_link_url: 'https://bluesky.example.com',
            gbp_topic_type: 'STANDARD',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($names)->toContain(
        'linkedin_link_url',
        'facebook_link_url',
        'post_url',
        'card_uri',
        'poll_options[]',
        'reply_to_id',
        'exclude_reply_user_ids[]',
        'threads_topic_tag',
        'reddit_link_url',
        'bluesky_link_url',
        'gbp_topic_type',
    )->and($contents['bluesky_link_url'])->toBe('https://bluesky.example.com');
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
    ))->addCommonTo($payload);

    $contents = array_column($payload->all(), 'contents', 'name');

    expect($contents['request_id'])->toBe('request-123')
        ->and($contents['discord_title'])->toBe('Discord caption')
        ->and($contents['telegram_title'])->toBe('Telegram caption');
});
