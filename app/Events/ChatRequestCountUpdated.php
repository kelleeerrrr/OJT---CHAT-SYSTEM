<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatRequestCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $adminId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->adminId . '.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.request.count.updated';
    }

    public function broadcastWith(): array
    {
        $pendingCount = Conversation::pending()
            ->where('partner_id', $this->adminId)
            ->count();

        return [
            'pending_count' => $pendingCount,
        ];
    }
}
