<?php

namespace App\Http\Controllers;

use App\Events\ConversationStatusChanged;
use App\Http\Requests\ConversationRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $adminId = Auth::id();

        $conversations = Conversation::pending()
            ->where('partner_id', $adminId)
            ->with(['user:id,name', 'partner:id,name'])
            ->latest()
            ->get()
            ->map(function ($conversation) {
                $firstMessage = Message::where(function ($q) use ($conversation) {
                    $q->where('sender_id', $conversation->user_id)
                      ->where('receiver_id', $conversation->partner_id);
                })->orWhere(function ($q) use ($conversation) {
                    $q->where('sender_id', $conversation->partner_id)
                      ->where('receiver_id', $conversation->user_id);
                })->oldest()->first();

                return [
                    'id' => $conversation->id,
                    'user' => $conversation->user,
                    'partner' => $conversation->partner,
                    'first_message' => $firstMessage ? $firstMessage->body : null,
                    'created_at' => $conversation->created_at->toISOString(),
                ];
            });

        return view('conversations.index', compact('conversations'));
    }

    public function update(ConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeAdmin();

        if (!$conversation->isPending()) {
            return response()->json(['error' => 'Conversation already processed.'], 400);
        }

        $action = $request->input('action');

        if ($action === 'accept') {
            $conversation->update([
                'status' => 'accepted',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            broadcast(new ConversationStatusChanged($conversation, $action))->toOthers();

            return response()->json([
                'status' => $conversation->status,
                'action' => $action,
                'redirect_url' => route('chat.show', $conversation->user),
            ]);
        } elseif ($action === 'reject') {
            $conversation->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            broadcast(new ConversationStatusChanged($conversation, $action))->toOthers();

            return response()->json([
                'status' => $conversation->status,
                'action' => $action,
            ]);
        }

        return response()->json(['error' => 'Invalid action.'], 400);
    }

    private function authorizeAdmin(): void
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
    }
}
