<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum LinkedinVisibility: string
{
    case Public = 'PUBLIC';
    case Connections = 'CONNECTIONS';
    case LoggedIn = 'LOGGED_IN';
    case Container = 'CONTAINER';
}
