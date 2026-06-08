<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum TiktokPrivacyLevel: string
{
    case PublicToEveryone = 'PUBLIC_TO_EVERYONE';
    case MutualFollowFriends = 'MUTUAL_FOLLOW_FRIENDS';
    case FollowerOfCreator = 'FOLLOWER_OF_CREATOR';
    case SelfOnly = 'SELF_ONLY';
}
