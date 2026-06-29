<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\Message;
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
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('users'));
    }

    public function show(User $user)
    {
        $this->abortIfDenied();

        $messages = Message::conversationBetween(Auth::id(), $user->id)
            ->get()
            ->map(fn ($m) => $this->formatMessage($m))
            ->values(); // IMPORTANT: reset index

        return view('chat.show', [
            'partner'  => $user,
            'messages' => $messages,
        ]);
    }

    public function send(SendMessageRequest $request): JsonResponse
    {
        $this->abortIfDenied();

        $sender = Auth::user();

        $sanitized = $this->sanitizer->sanitize($request->body);

        if ($this->sanitizer->isEmpty($sanitized)) {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        ['text' => $filtered, 'has_bad_words' => $hasBad] =
            $this->filter->process($sanitized);

        $message = Message::create([
            'sender_id'     => $sender->id,
            'receiver_id'   => $request->receiver_id,
            'body'          => $filtered,
            'body_raw'      => $hasBad ? $sanitized : null,
            'has_bad_words' => $hasBad,
        ]);

        $message->load('sender:id,name', 'receiver:id,name');

        $tempId = $request->input('temp_id');

        // Broadcast the message using Reverb connection
        try {
            broadcast(new MessageSent($message, $sender, $tempId))->toOthers();
        } catch (\Exception $e) {
            // Log broadcast error but don't fail the request
            \Log::error('Broadcast failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => $this->formatMessage($message, $tempId),
        ], 201);
    }

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

    public function markRead(User $user): JsonResponse
    {
        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function abortIfDenied(): void
    {
        if (Auth::user()->isChatDenied()) {
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