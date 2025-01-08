<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case ADMIN = 'ADMIN';
    case DEVELOPER = 'DEVELOPER';
    case VETERINARIAN = 'VETERINARIAN';
}
