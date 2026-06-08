<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum YoutubePrivacyStatus: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
