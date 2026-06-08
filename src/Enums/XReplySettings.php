<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum XReplySettings: string
{
    case Everyone = 'everyone';
    case Following = 'following';
    case MentionedUsers = 'mentionedUsers';
    case Subscribers = 'subscribers';
    case Verified = 'verified';
}
