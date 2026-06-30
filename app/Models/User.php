<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_chat_denied',
        'chat_denied_at',
        'chat_denied_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'chat_denied_at'    => 'datetime',
        'is_chat_denied'    => 'boolean',
        'password'          => 'hashed',
    ];

    // ── Role helpers ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isChatDenied(): bool
    {
        return (bool) $this->is_chat_denied;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function chatDenyLogs(): HasMany
    {
        return $this->hasMany(ChatDenyLog::class, 'user_id');
    }

    /**
     * The admin/superadmin who denied this user's chat access.
     */
    public function deniedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'chat_denied_by');
    }

    // ── Unread message counts ─────────────────────────────────────────────────

    /**
     * Number of messages sent TO this user that have not been read.
     * Use in Blade: $user->unread_received_count
     */
    public function getUnreadReceivedCountAttribute(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    /**
     * Number of messages sent BY this user that the recipient hasn't read.
     * Use in Blade: $user->unread_sent_count
     */
    public function getUnreadSentCountAttribute(): int
    {
        return $this->sentMessages()->where('is_read', false)->count();
    }

    /**
     * Eager-loadable scope for admin list pages.
     * Usage: User::withCount(['receivedMessages as unread_count' => fn($q) => $q->where('is_read', false)])->get()
     *
     * Convenience static helper so the blade / controller can call:
     *   User::withUnreadCount()->where('role', 'user')->get()
     */
    public function scopeWithUnreadCount($query)
    {
        return $query->withCount([
            'receivedMessages as unread_count' => fn ($q) => $q->where('is_read', false),
        ]);
    }

    // ── Conversation helpers ──────────────────────────────────────────────────

    /**
     * Returns other users this user has had a conversation with,
     * ordered by most recent message.
     */
    public function conversationPartners()
    {
        $id = $this->id;

        return User::whereIn('id', function ($q) use ($id) {
            $q->select('receiver_id')
                ->from('messages')
                ->where('sender_id', $id)
                ->where('is_deleted', false)
                ->union(
                    DB::table('messages')
                        ->select('sender_id')
                        ->where('receiver_id', $id)
                        ->where('is_deleted', false)
                );
        })->where('id', '!=', $id);
    }

    /**
     * Latest message exchanged between this user and $partnerId.
     */
    public function latestMessageWith(int $partnerId): ?Message
    {
        return Message::where('is_deleted', false)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('sender_id', $this->id)->where('receiver_id', $partnerId))
                ->orWhere(fn ($q) => $q->where('sender_id', $partnerId)->where('receiver_id', $this->id))
            )
            ->latest()
            ->first();
    }

    /**
     * Count of unread messages in the conversation with $partnerId
     * (i.e. messages sent TO this user by $partnerId that are unread).
     */
    public function unreadCountFrom(int $partnerId): int
    {
        return $this->receivedMessages()
            ->where('sender_id', $partnerId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark all messages received from $partnerId as read.
     */
    public function markMessagesReadFrom(int $partnerId): void
    {
        $this->receivedMessages()
            ->where('sender_id', $partnerId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * First letter of the user's name, uppercased — for avatar initials.
     * Use in Blade: $user->initials
     */
    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    /**
     * Human-readable role label.
     * Use in Blade: $user->role_label
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'Superadmin',
            'admin'      => 'Admin',
            default      => 'User',
        };
    }
}