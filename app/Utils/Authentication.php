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
}
