<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum Platform: string
{
    case TikTok = 'tiktok';
    case Instagram = 'instagram';
    case YouTube = 'youtube';
    case LinkedIn = 'linkedin';
    case Facebook = 'facebook';
    case Pinterest = 'pinterest';
    case Threads = 'threads';
    case Reddit = 'reddit';
    case Bluesky = 'bluesky';
    case Discord = 'discord';
    case Telegram = 'telegram';
    case GoogleBusiness = 'google_business';
    case X = 'x';
}
