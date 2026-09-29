<?php
// app/Traits/ResolvesAuthUser.php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait ResolvesAuthUser
{
    /**
     * Try all known guards, return the first authenticated user.
     * Returns null if nobody is authenticated.
     */
    protected function resolveUser()
    {
        foreach (['sanctum', 'admin', 'web', 'api'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) return $user;
        }
        return Auth::user(); // final fallback
    }
}