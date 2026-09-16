<?php

declare(strict_types=1);

namespace Softgeng\UploadPost\Enums;

enum GoogleBusinessMediaCategory: string
{
    case Cover = 'COVER';
    case Profile = 'PROFILE';
    case Logo = 'LOGO';
    case Exterior = 'EXTERIOR';
    case Interior = 'INTERIOR';
    case Product = 'PRODUCT';
    case AtWork = 'AT_WORK';
    case FoodAndDrink = 'FOOD_AND_DRINK';
    case Menu = 'MENU';
    case CommonArea = 'COMMON_AREA';
    case Rooms = 'ROOMS';
    case Teams = 'TEAMS';
    case Additional = 'ADDITIONAL';
}
