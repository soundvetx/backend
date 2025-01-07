<?php

namespace App\Utils;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Authentication
{
    public static function user(): ?User
    {
        return Auth::user();
    }

    public static function randomPassword(): string
    {
        return substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
    }
}
