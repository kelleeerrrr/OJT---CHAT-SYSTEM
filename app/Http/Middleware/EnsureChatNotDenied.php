<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        if (Auth::check() && Auth::user()->isChatDenied() && ! $this->canContactSuperAdmin($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Your account has been restricted. Contact the super admin to restore access.',
                ], 403);
            }

            return redirect()
                ->route('chat.index')
                ->with('error', 'Your account has been restricted. Contact the super admin to restore access.');
        }

        return $next($request);
    }

    private function canContactSuperAdmin(Request $request): bool
    {
        $routeUser = $request->route('user');

        if ($routeUser instanceof User) {
            return $routeUser->isSuperAdmin();
        }

        if ($request->routeIs('chat.send')) {
            $receiverId = $request->integer('receiver_id');

            if (! $receiverId) {
                return false;
            }

            $receiver = User::find($receiverId);

            return $receiver?->isSuperAdmin() ?? false;
        }

        return false;
    }
}
