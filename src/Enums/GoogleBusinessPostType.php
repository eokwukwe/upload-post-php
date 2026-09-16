<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum GoogleBusinessPostType: string
{
    case Media = 'MEDIA';
    case Photo = 'PHOTO';
    case Gallery = 'GALLERY';
}
