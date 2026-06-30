<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Services\BadWordFilter;
use App\Services\MessageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(
        private readonly MessageSanitizer $sanitizer,
        private readonly BadWordFilter $filter,
    ) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $authId = Auth::id();

        $users = User::where('id', '!=', $authId)
            ->select('id', 'name', 'email', 'role')
            // Unread = messages THEY sent TO ME that I haven't read
            ->withCount([
                'sentMessages as unread_count' => fn ($q) => $q
                    ->where('receiver_id', $authId)
                    ->whereNull('read_at')
                    ->where('is_deleted', false),
            ])
            // Latest message timestamp for sorting
            ->addSelect([
                'last_message_at' => Message::select('created_at')
                    ->where('is_deleted', false)
                    ->where(fn ($q) => $q
                        ->where(fn ($q2) => $q2
                            ->whereColumn('sender_id', 'users.id')
                            ->where('receiver_id', $authId))
                        ->orWhere(fn ($q2) => $q2
                            ->where('sender_id', $authId)
                            ->whereColumn('receiver_id', 'users.id'))
                    )
                    ->latest()
                    ->limit(1),
            ])
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at')
            ->orderBy('name')
            ->get();

        // Attach latest message preview per user (one extra query, avoids N+1)
        $userIds = $users->pluck('id');

        $latestMessages = Message::where('is_deleted', false)
            ->where(fn ($q) => $q
                ->where('sender_id', $authId)->whereIn('receiver_id', $userIds)
                ->orWhere(fn ($q2) => $q2->whereIn('sender_id', $userIds)->where('receiver_id', $authId))
            )
            ->select('id', 'sender_id', 'receiver_id', 'body', 'read_at', 'created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($m) => $m->sender_id === $authId ? $m->receiver_id : $m->sender_id)
            ->map(fn ($group) => $group->first()); // already ordered desc so first = latest

        $users->each(fn ($u) => $u->latest_message = $latestMessages->get($u->id));

        return view('chat.index', compact('users'));
    }

    // ── Inbox data (JSON — polled every 5 s from the index page) ─────────────

    public function inbox(): JsonResponse
    {
        $authId = Auth::id();

        $users = User::where('id', '!=', $authId)
            ->select('id', 'name', 'email', 'role')
            ->withCount([
                'sentMessages as unread_count' => fn ($q) => $q
                    ->where('receiver_id', $authId)
                    ->whereNull('read_at')
                    ->where('is_deleted', false),
            ])
            ->get();

        $userIds = $users->pluck('id');

        $latestMessages = Message::where('is_deleted', false)
            ->where(fn ($q) => $q
                ->where('sender_id', $authId)->whereIn('receiver_id', $userIds)
                ->orWhere(fn ($q2) => $q2->whereIn('sender_id', $userIds)->where('receiver_id', $authId))
            )
            ->select('id', 'sender_id', 'receiver_id', 'body', 'read_at', 'created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($m) => $m->sender_id === $authId ? $m->receiver_id : $m->sender_id)
            ->map(fn ($group) => $group->first());

        $data = $users->map(fn ($u) => [
            'id'           => $u->id,
            'unread_count' => $u->unread_count,
            'preview'      => $latestMessages->has($u->id)
                ? mb_strimwidth($latestMessages->get($u->id)->body, 0, 80, '…')
                : null,
            'time_ago'     => $latestMessages->has($u->id)
                ? $latestMessages->get($u->id)->created_at->diffForHumans()
                : null,
        ]);

        return response()->json($data);
    }

    // ── Show thread ───────────────────────────────────────────────────────────

    public function show(User $user)
    {
        $this->abortIfDenied();

        $conversation = Conversation::findBetween(Auth::id(), $user->id);
        $messageCount = Message::conversationBetween(Auth::id(), $user->id)->count();

        $messages = Message::conversationBetween(Auth::id(), $user->id)
            ->get()
            ->map(fn ($m) => $this->formatMessage($m))
            ->values();

        // Clear unread badge immediately when thread is opened
        Message::markConversationRead($user->id, Auth::id());

        return view('chat.show', [
            'partner'            => $user,
            'messages'           => $messages,
            'conversationStatus' => 'accepted',
            'messageCount'       => $messageCount,
        ]);
    }

    // ── Send ──────────────────────────────────────────────────────────────────

    public function send(SendMessageRequest $request): JsonResponse
    {
        $this->abortIfDenied();

        if (! Setting::isChatEnabled()) {
            return response()->json(['error' => 'Chat is currently disabled by the administrator.'], 403);
        }

        $sender     = Auth::user();
        $receiverId = $request->receiver_id;

        $receiver = User::find($receiverId);
        if (! $receiver) {
            return response()->json(['error' => 'Receiver not found.'], 404);
        }

        $requiresApproval = ! $sender->isAdmin() && $receiver->isAdmin();

        if ($requiresApproval) {
            $conversation = Conversation::findOrCreate($sender->id, $receiverId);

            if ($conversation->isRejected()) {
                return response()->json(['error' => 'Your chat request has been declined.'], 403);
            }

            if ($conversation->isPending()) {
                $messageCount = Message::where(fn ($q) => $q
                    ->where('sender_id', $sender->id)->where('receiver_id', $receiverId))
                    ->orWhere(fn ($q) => $q
                    ->where('sender_id', $receiverId)->where('receiver_id', $sender->id))
                    ->count();

                if ($messageCount > 0) {
                    return response()->json(['error' => 'Waiting for Admin approval.'], 403);
                }
            }
        } else {
            $conversation = Conversation::findOrCreate($sender->id, $receiverId);
            if ($conversation->isPending()) {
                $conversation->update(['status' => 'accepted']);
            }
        }

        $sanitized = $this->sanitizer->sanitize($request->body);

        if ($this->sanitizer->isEmpty($sanitized)) {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        ['text' => $filtered, 'has_bad_words' => $hasBad] =
            $this->filter->process($sanitized);

        $message = Message::create([
            'sender_id'       => $sender->id,
            'receiver_id'     => $receiverId,
            'body'            => $filtered,
            'body_raw'        => $hasBad ? $sanitized : null,
            'has_bad_words'   => $hasBad,
            'conversation_id' => $conversation->id,
        ]);

        $message->load('sender:id,name', 'receiver:id,name');

        $tempId = $request->input('temp_id');

        try {
            broadcast(new MessageSent($message, $sender, $tempId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Broadcast failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => $this->formatMessage($message, $tempId),
        ], 201);
    }

    // ── History ───────────────────────────────────────────────────────────────

    public function history(Request $request, User $user): JsonResponse
    {
        $this->abortIfDenied();

        $messages = Message::conversationBetween(Auth::id(), $user->id)
            ->latest('id')
            ->paginate(30);

        return response()->json([
            'data' => $messages->getCollection()
                ->map(fn ($m) => $this->formatMessage($m))
                ->reverse()
                ->values(),
            'next_page' => $messages->nextPageUrl(),
            'has_more'  => $messages->hasMorePages(),
        ]);
    }

    // ── Mark read ─────────────────────────────────────────────────────────────

    public function markRead(User $user): JsonResponse
    {
        Message::markConversationRead($user->id, Auth::id());

        return response()->json(['ok' => true]);
    }

    // ── Delete own message ────────────────────────────────────────────────────

    public function destroy(Message $message): JsonResponse
    {
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $message->softFlag();

        return response()->json(['ok' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function abortIfDenied(): void
    {
        if (Auth::user()->isChatDenied()) {
            abort(403);
        }
    }

    private function formatMessage(Message $m, ?string $tempId = null): array
    {
        return [
            'id'            => $m->id,
            'temp_id'       => $tempId,
            'sender_id'     => $m->sender_id,
            'receiver_id'   => $m->receiver_id,
            'sender_name'   => $m->sender->name ?? 'Unknown',
            'body'          => $m->body,
            'has_bad_words' => $m->has_bad_words,
            'read_at'       => $m->read_at?->toISOString(),
            'preview'       => mb_strimwidth($m->body ?? '', 0, 80, '…'),
            'time_ago'      => $m->created_at->diffForHumans(),
            'created_at'    => $m->created_at->toISOString(),
        ];
    }
}