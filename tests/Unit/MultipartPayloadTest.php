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
            x_quote_tweet_id: 'tweet-123',
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
            x_quote_tweet_id: 'tweet-123',
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
            tiktok_disable_comment: true,
            youtube_tags: ['sdk'],
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
            instagram_media_type: 'IMAGE',
            pinterest_board_id: 'board-123',
            x_quote_tweet_id: 'tweet-123',
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
            linkedin_visibility: 'PUBLIC',
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
            ],
            title: 'Video',
        ),
        options: new PlatformOptions(
            tiktok_disable_comment: true,
            tiktok_privacy_level: 'PUBLIC_TO_EVERYONE',
            tiktok_disable_duet: true,
            tiktok_disable_stitch: true,
            tiktok_cover_timestamp: 3,
            tiktok_is_aigc: true,
            tiktok_post_mode: 'DIRECT_POST',
            instagram_media_type: 'REELS',
            instagram_collaborators: 'user',
            instagram_user_tags: 'tag',
            instagram_location_id: 'location',
            instagram_share_to_feed: true,
            instagram_cover: 'https://example.com/cover.jpg',
            instagram_audio_name: 'audio',
            instagram_thumb_offset: '1',
            youtube_tags: ['sdk'],
            youtube_category_id: '22',
            youtube_privacy_status: 'public',
            youtube_embeddable: true,
            youtube_license: 'youtube',
            youtube_public_stats_viewable: true,
            youtube_thumbnail_url: 'https://example.com/thumb.jpg',
            youtube_self_declared_made_for_kids: false,
            youtube_contains_synthetic_media: true,
            youtube_default_language: 'en',
            youtube_default_audio_language: 'en',
            youtube_allowed_countries: 'US',
            youtube_blocked_countries: 'CA',
            youtube_has_paid_product_placement: true,
            youtube_recording_date: '2026-01-01',
            youtube_subtitles: [new YoutubeSubtitleData(language: 'en', url: 'https://example.com/subtitles.vtt')],
            linkedin_visibility: 'PUBLIC',
            target_linkedin_page_id: 'linkedin-page',
            facebook_page_id: 'facebook-page',
            facebook_video_state: 'PUBLISHED',
            facebook_media_type: 'REELS',
            thumbnail_url: 'https://example.com/fb-thumb.jpg',
            pinterest_board_id: 'board',
            pinterest_alt_text: 'Alt',
            pinterest_link: 'https://example.com',
            pinterest_cover_image_url: 'https://example.com/pinterest-cover.jpg',
            pinterest_cover_image_content_type: 'image/jpeg',
            pinterest_cover_image_data: 'base64',
            pinterest_cover_image_key_frame_time: 5,
            x_reply_settings: 'following',
            x_nullcast: true,
            x_quote_tweet_id: 'quote',
            x_geo_place_id: 'geo',
            x_for_super_followers_only: true,
            x_community_id: 'community',
            x_share_with_followers: true,
            x_direct_message_deep_link: 'https://x.example.com/dm',
            x_long_text_as_post: true,
            x_tagged_user_ids: ['user-1'],
            x_place_id: 'place',
            x_thread_image_layout: 'grid',
            threads_long_text_as_post: true,
            threads_thread_media_layout: 'carousel',
            threads_topic_tag: 'php',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');

    expect($names)->toContain(
        'privacy_level',
        'cover_url',
        'youtube_subtitle_file_0',
        'visibility',
        'facebook_page_id',
        'pinterest_cover_image_url',
        'tagged_user_ids[]',
        'threads_topic_tag',
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
                Platform::Reddit,
            ],
            title: 'Photos',
        ),
        options: new PlatformOptions(
            tiktok_auto_add_music: true,
            tiktok_photo_cover_index: 1,
            instagram_media_type: 'IMAGE',
            linkedin_visibility: 'PUBLIC',
            facebook_page_id: 'facebook-page',
            pinterest_board_id: 'board',
            x_reply_settings: 'everyone',
            x_tagged_user_ids: ['user-1'],
            threads_topic_tag: 'php',
            reddit_subreddit: 'php',
            reddit_flair_id: 'flair',
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
        'subreddit',
        'threads_topic_tag',
    )->not->toContain('reply_settings');
});

test('text options include selected platform-specific fields', function (): void {
    $parts = (new UploadTextData(
        common: new CommonUploadData(
            user: 'profile',
            platforms: [Platform::LinkedIn, Platform::Facebook, Platform::X, Platform::Threads, Platform::Reddit, Platform::Bluesky],
            title: 'Text',
        ),
        link_url: 'https://example.com',
        options: new PlatformOptions(
            linkedin_visibility: 'PUBLIC',
            facebook_link_url: 'https://facebook.example.com',
            x_post_url: 'https://x.example.com/post',
            x_card_uri: 'card',
            x_poll_options: ['yes', 'no'],
            x_poll_duration: 60,
            x_poll_reply_settings: 'following',
            threads_topic_tag: 'php',
            reddit_subreddit: 'php',
            reddit_link_url: 'https://reddit.example.com',
            bluesky_link_url: 'https://bluesky.example.com',
        ),
    ))->toMultipart()->all();

    $names = array_column($parts, 'name');
    $contents = array_column($parts, 'contents', 'name');

    expect($names)->toContain(
        'linkedin_link_url',
        'facebook_link_url',
        'post_url',
        'poll_options[]',
        'threads_topic_tag',
        'reddit_link_url',
        'bluesky_link_url',
    )->and($contents['bluesky_link_url'])->toBe('https://bluesky.example.com');
});
