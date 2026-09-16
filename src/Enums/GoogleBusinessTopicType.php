<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum GoogleBusinessTopicType: string
{
    case Standard = 'STANDARD';
    case Event = 'EVENT';
    case Offer = 'OFFER';
}
