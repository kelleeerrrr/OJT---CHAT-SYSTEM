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

class ChatController extends Controller
{
    public function __construct(
        private readonly MessageSanitizer $sanitizer,
        private readonly BadWordFilter $filter,
    ) {}

    public function index()
    {
        $users = User::where('id', '!=', Auth::id())
            ->orderedByRolePriority()
            ->get();

        return view('chat.index', compact('users'));
    }

    public function show(User $user)
    {
        $this->abortIfDeniedAgainst($user);

        $conversation = Conversation::findBetween(Auth::id(), $user->id);
        $messageCount = Message::conversationBetween(Auth::id(), $user->id)->count();

        // Always allow showing input - backend will enforce approval rules
        $conversationStatus = 'accepted';

        $messages = Message::conversationBetween(Auth::id(), $user->id)
            ->get()
            ->map(fn ($m) => $this->formatMessage($m))
            ->values(); // IMPORTANT: reset index

        return view('chat.show', [
            'partner'  => $user,
            'messages' => $messages,
            'conversationStatus' => $conversationStatus,
            'messageCount' => $messageCount,
        ]);
    }

    public function send(SendMessageRequest $request): JsonResponse
    {
        // Check if chat is globally enabled
        if (!Setting::isChatEnabled()) {
            return response()->json(['error' => 'Chat is currently disabled by the administrator.'], 403);
        }

        $sender = Auth::user();
        $receiverId = $request->receiver_id;

        // Get receiver to check role
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json(['error' => 'Receiver not found.'], 404);
        }

        $this->abortIfDeniedAgainst($receiver);

        // Only require approval if sender is user and receiver is admin/superadmin
        $requiresApproval = !$sender->isAdmin() && $receiver->isAdmin();

        if ($requiresApproval) {
            // Get or create conversation
            $conversation = Conversation::findOrCreate($sender->id, $receiverId);

            // Check conversation status
            if ($conversation->isRejected()) {
                return response()->json(['error' => 'Your chat request has been declined.'], 403);
            }

            if ($conversation->isPending()) {
                // Check if this is the first message
                $messageCount = Message::where(function ($q) use ($sender, $receiverId) {
                    $q->where('sender_id', $sender->id)
                      ->where('receiver_id', $receiverId);
                })->orWhere(function ($q) use ($sender, $receiverId) {
                    $q->where('sender_id', $receiverId)
                      ->where('receiver_id', $sender->id);
                })->count();

                if ($messageCount > 0) {
                    return response()->json(['error' => 'Waiting for Admin approval.'], 403);
                }
            }
        } else {
            // No approval needed - create conversation as accepted if it doesn't exist
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
            'sender_id'     => $sender->id,
            'receiver_id'   => $receiverId,
            'body'          => $filtered,
            'body_raw'      => $hasBad ? $sanitized : null,
            'has_bad_words' => $hasBad,
            'conversation_id' => $conversation->id,
        ]);

        $message->load('sender:id,name', 'receiver:id,name');

        $tempId = $request->input('temp_id');

        // Broadcast the message using Pusher
        broadcast(new MessageSent($message, $sender, $tempId))->toOthers();

        return response()->json([
            'message' => $this->formatMessage($message, $tempId),
        ], 201);
    }

    public function history(Request $request, User $user): JsonResponse
    {
        $this->abortIfDeniedAgainst($user);

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

    public function markRead(User $user): JsonResponse
    {
        $this->abortIfDeniedAgainst($user);

        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function abortIfDeniedAgainst(User $partner): void
    {
        if (Auth::user()->isChatDenied() && ! $partner->isSuperAdmin()) {
            abort(403);
        }
    }

    private function formatMessage(Message $m): array
    {
        return [
            'id'            => $m->id,
            'sender_id'     => $m->sender_id,
            'receiver_id'   => $m->receiver_id,
            'sender_name'   => $m->sender->name ?? 'Unknown',
            'body'          => $m->body,
            'has_bad_words' => $m->has_bad_words,
            'read_at'       => optional($m->read_at)?->toISOString(),
            'created_at'    => $m->created_at->toISOString(),
        ];
    }
}