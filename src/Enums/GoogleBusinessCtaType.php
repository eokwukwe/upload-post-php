<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum GoogleBusinessCtaType: string
{
    case Book = 'BOOK';
    case Order = 'ORDER';
    case Shop = 'SHOP';
    case LearnMore = 'LEARN_MORE';
    case SignUp = 'SIGN_UP';
    case Call = 'CALL';
}
