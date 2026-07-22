<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountBanned
{
    /**
     * Handle an incoming request.
     * Rejects banned users with a specific ACCOUNT_BANNED error code.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !is_null($user->banned_at)) {
            return response()->json([
                'error' => 'ACCOUNT_BANNED',
                'message' => 'Sua conta foi suspensa.',
                'reason' => $user->ban_reason,
            ], 403);
        }

        return $next($request);
    }
}
