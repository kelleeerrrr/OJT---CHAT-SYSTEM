<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $action
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->conversation->user_id . '.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return '.conversation.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'status' => $this->conversation->status,
            'action' => $this->action,
            'partner_id' => $this->conversation->partner_id,
        ];
    }
}
