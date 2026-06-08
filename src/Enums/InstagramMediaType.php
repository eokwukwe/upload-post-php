<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum InstagramMediaType: string
{
    case Reels = 'REELS';
    case Stories = 'STORIES';
    case Image = 'IMAGE';
}
