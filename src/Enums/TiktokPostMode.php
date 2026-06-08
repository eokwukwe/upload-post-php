<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum TiktokPostMode: string
{
    case DirectPost = 'DIRECT_POST';
    case MediaUpload = 'MEDIA_UPLOAD';
}
