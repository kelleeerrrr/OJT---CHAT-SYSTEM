<?php

namespace App\Http\Controllers;

use App\Events\ChatAccessChanged;
use App\Models\ChatDenyLog;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            Gate::authorize('manage-chat');
            return $next($request);
        });
    }

    /**
     * Admin dashboard – list all users with chat status.
     */
    public function index()
    {
        $users = User::where('role', 'user')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.chat.index', compact('users'));
    }

    /**
     * Superadmin user management dashboard.
     */
    public function manageUsers()
    {
        $users = User::where('role', '!=', 'superadmin')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.chat.manage-users', compact('users'));
    }

    /**
     * Deny a user's chat access.
     */
    public function deny(Request $request, User $user): RedirectResponse
    {
        // Admins cannot deny other admins; only superadmin can
        if ($user->isAdmin() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate(['reason' => 'nullable|string|max:255']);

        $user->update([
            'is_chat_denied'  => true,
            'chat_denied_at'  => now(),
            'chat_denied_by'  => Auth::id(),
        ]);

        ChatDenyLog::create([
            'user_id'  => $user->id,
            'admin_id' => Auth::id(),
            'action'   => 'denied',
            'reason'   => $request->input('reason'),
        ]);

        // Notify the user in real-time via their private notification channel
        broadcast(new ChatAccessChanged($user, 'denied', $request->input('reason', '')));

        return back()->with('status', "Chat access denied for {$user->name}.");
    }

    /**
     * Restore a user's chat access.
     */
    public function restore(User $user): RedirectResponse
    {
        $user->update([
            'is_chat_denied'  => false,
            'chat_denied_at'  => null,
            'chat_denied_by'  => null,
        ]);

        ChatDenyLog::create([
            'user_id'  => $user->id,
            'admin_id' => Auth::id(),
            'action'   => 'restored',
        ]);

        broadcast(new ChatAccessChanged($user, 'restored'));

        return back()->with('status', "Chat access restored for {$user->name}.");
    }

    /**
     * View full conversation between two users (admin audit).
     */
    public function conversation(User $userA, User $userB)
    {
        $messages = Message::conversationBetween($userA->id, $userB->id)
            ->withTrashed()     // include soft-deleted
            ->get();

        return view('admin.chat.conversation', compact('userA', 'userB', 'messages'));
    }

    /**
     * Delete (soft-delete) a specific message.
     */
    public function deleteMessage(Message $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Message removed.');
    }

    /**
     * Deny log for a specific user.
     */
    public function denyLog(User $user)
    {
        $logs = $user->chatDenyLogs()
            ->with('admin:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($logs);
    }
}
