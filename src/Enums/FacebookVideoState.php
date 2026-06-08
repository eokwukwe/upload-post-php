<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum FacebookVideoState: string
{
    case Published = 'PUBLISHED';
    case Draft = 'DRAFT';
}
