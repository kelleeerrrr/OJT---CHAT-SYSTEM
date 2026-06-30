<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureChatNotDenied
{
    /**
     * Reject requests from users whose chat access has been suspended.
     * Returns JSON for XHR/API requests, redirects otherwise.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isChatDenied()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Your chat access has been suspended by an administrator.',
                ], 403);
            }

            return redirect()
                ->route('chat.index')
                ->with('error', 'Your chat access has been suspended by an administrator.');
        }

        return $next($request);
    }
}
