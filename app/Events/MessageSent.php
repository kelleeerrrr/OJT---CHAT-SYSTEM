<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly User $sender,
        public readonly ?string $tempId = null,
    ) {}

    /**
     * Private channel named after the canonical pair of user IDs.
     * Example: chat.2.3
     */
    public function broadcastOn(): array
    {
        [$a, $b] = $this->sortedPair(
            $this->message->sender_id,
            $this->message->receiver_id
        );

        return [
            new PrivateChannel("chat.{$a}.{$b}")
        ];
    }

    /**
     * Frontend event name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Data sent to Pusher.
     */
    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'sender_id'     => $this->message->sender_id,
            'receiver_id'   => $this->message->receiver_id,
            'sender_name'   => $this->sender->name,
            'body'          => $this->message->body,
            'has_bad_words' => $this->message->has_bad_words,
            'created_at'    => $this->message->created_at->toISOString(),
            'temp_id'       => $this->tempId,
        ];
        logger('MESSAGE SENT EVENT', [
            'id' => $this->message->id,
            'sender' => $this->message->sender_id,
            'receiver' => $this->message->receiver_id,
        ]);
    }

    /**
     * Sort user IDs so both users share the same channel.
     */
    private function sortedPair(int $a, int $b): array
    {
        return $a < $b
            ? [$a, $b]
            : [$b, $a];
    }
}