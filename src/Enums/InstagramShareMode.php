<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum InstagramShareMode: string
{
    case Custom = 'CUSTOM';
    case TrialReelsShareToFollowersIfLiked = 'TRIAL_REELS_SHARE_TO_FOLLOWERS_IF_LIKED';
    case TrialReelsDontShareToFollowers = 'TRIAL_REELS_DONT_SHARE_TO_FOLLOWERS';
}
