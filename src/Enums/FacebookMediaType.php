<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum FacebookMediaType: string
{
    case Reels = 'REELS';
    case Stories = 'STORIES';
    case Video = 'VIDEO';
}
