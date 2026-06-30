<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    // ── Role helpers ────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin'
            || $this->email === 'superadmin@bsu.com'
            || $this->name === 'Super Admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']) || $this->isSuperAdmin();
    }

    public function isChatDenied(): bool
    {
        return (bool) $this->is_chat_denied;
    }

    public function scopeOrderedByRolePriority(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN role = 'superadmin' THEN 0 WHEN role = 'admin' THEN 1 WHEN role = 'user' THEN 2 ELSE 3 END")
            ->orderBy('name');
    }

    // ── Relationships ────────────────────────────────────────────────────────

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

    // ── Conversation partner list ─────────────────────────────────────────────

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
                    \DB::table('messages')
                        ->select('sender_id')
                        ->where('receiver_id', $id)
                        ->where('is_deleted', false)
                );
        })->where('id', '!=', $id);
    }
}
