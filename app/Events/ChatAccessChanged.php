<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatAccessChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User   $targetUser,
        public readonly string $action,    // 'denied' | 'restored'
        public readonly string $reason = '',
    ) {}

    public function broadcastOn(): array
    {
        // Each user listens on their own private notification channel
        return [
            new PrivateChannel("user.{$this->targetUser->id}.notifications"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.access.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'reason' => $this->reason,
        ];
    }
}
