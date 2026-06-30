<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'body_raw',
        'has_bad_words',
        'is_deleted',
        'read_at',
        'conversation_id',
    ];

    protected $casts = [
        'has_bad_words' => 'boolean',
        'is_deleted'    => 'boolean',
        'read_at'       => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    protected $hidden = [
        'body_raw',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // ── Read / unread state ───────────────────────────────────────────────────

    /**
     * Whether this message has been read by the receiver.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Mark this message as read (sets read_at to now).
     * No-op if already read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark this message as unread (clears read_at).
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Bulk-mark a set of messages as read.
     * Usage: Message::markManyAsRead([1, 2, 3])
     */
    public static function markManyAsRead(array $ids): int
    {
        return static::whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all unread messages sent TO $receiverId BY $senderId as read.
     * Call this when the receiver opens the conversation.
     */
    public static function markConversationRead(int $senderId, int $receiverId): int
    {
        return static::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->update(['read_at' => now()]);
    }

    // ── Soft-delete helpers ───────────────────────────────────────────────────

    /**
     * Logical delete (sets is_deleted flag).
     * Separate from Laravel's SoftDeletes which uses deleted_at.
     */
    public function softFlag(): void
    {
        if (! $this->is_deleted) {
            $this->update(['is_deleted' => true]);
        }
    }

    /**
     * Restore a logically deleted message.
     */
    public function restoreFlag(): void
    {
        $this->update(['is_deleted' => false]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Only messages not logically deleted. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    /** Only messages that have been read. */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /** Only messages that have not been read. */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /** Only messages flagged for bad words. */
    public function scopeFlagged(Builder $query): Builder
    {
        return $query->where('has_bad_words', true);
    }

    /** Messages sent by a specific user. */
    public function scopeFrom(Builder $query, int $userId): Builder
    {
        return $query->where('sender_id', $userId);
    }

    /** Messages received by a specific user. */
    public function scopeTo(Builder $query, int $userId): Builder
    {
        return $query->where('receiver_id', $userId);
    }

    /** Messages involving a user as sender or receiver. */
    public function scopeInvolving(Builder $query, int $userId): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
        );
    }

    // ── Static query helpers ──────────────────────────────────────────────────

    /**
     * Full conversation thread between two users, chronological.
     */
    public static function conversationBetween(int $userA, int $userB): Builder
    {
        return static::with([
                'sender:id,name,email',
                'receiver:id,name,email',
            ])
            ->where(function ($query) use ($userA, $userB) {
                $query->where(fn ($q) => $q
                        ->where('sender_id', $userA)
                        ->where('receiver_id', $userB))
                      ->orWhere(fn ($q) => $q
                        ->where('sender_id', $userB)
                        ->where('receiver_id', $userA));
            })
            ->where('is_deleted', false)
            ->orderBy('id', 'asc');
    }

    /**
     * Unread message count sent TO $receiverId.
     * Optionally scoped to a specific sender.
     */
    public static function unreadCountFor(int $receiverId, ?int $senderId = null): int
    {
        return static::where('receiver_id', $receiverId)
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->when($senderId, fn ($q) => $q->where('sender_id', $senderId))
            ->count();
    }

    /**
     * Latest message between two users — for conversation list previews.
     */
    public static function latestBetween(int $userA, int $userB): ?static
    {
        return static::conversationBetween($userA, $userB)->latest('id')->first();
    }

    /**
     * Admin thread view — includes soft-deleted rows and role info.
     */
    public static function adminThreadBetween(int $userA, int $userB): Builder
    {
        return static::withTrashed()
            ->with([
                'sender:id,name,email,role',
                'receiver:id,name,email,role',
            ])
            ->where(function ($query) use ($userA, $userB) {
                $query->where(fn ($q) => $q->where('sender_id', $userA)->where('receiver_id', $userB))
                      ->orWhere(fn ($q) => $q->where('sender_id', $userB)->where('receiver_id', $userA));
            })
            ->orderBy('id', 'asc');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Short preview of the message body (first 80 chars).
     * Use in Blade: $message->preview
     */
    public function getPreviewAttribute(): string
    {
        return mb_strimwidth($this->body ?? '', 0, 80, '…');
    }

    /**
     * Human-friendly "time ago" label.
     * Use in Blade: $message->time_ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '—';
    }

    /**
     * True when this message was sent today.
     * Use in Blade: $message->is_today
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->created_at?->isToday() ?? false;
    }
}