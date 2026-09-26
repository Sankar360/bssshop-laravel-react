<?php
// app/Http/Middleware/SuperAdminOnly.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminOnly
{
    /**
     * Block non-super-admins from sensitive endpoints.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || (int) $user->super_admin !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin access required.',
            ], 403);
        }

        return $next($request);
    }
}