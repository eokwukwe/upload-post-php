<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum FacebookUnpublishedContentType: string
{
    case Draft = 'DRAFT';
    case InlineCreated = 'INLINE_CREATED';
    case AdsPost = 'ADS_POST';
}
