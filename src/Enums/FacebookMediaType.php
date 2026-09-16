<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum FacebookMediaType: string
{
    case Posts = 'POSTS';
    case Reels = 'REELS';
    case Stories = 'STORIES';
    case Video = 'VIDEO';
}
