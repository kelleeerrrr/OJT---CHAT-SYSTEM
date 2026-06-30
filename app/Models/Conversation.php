<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'partner_id',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public static function findOrCreate(int $senderId, int $receiverId): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $senderId,
                'partner_id' => $receiverId,
            ],
            [
                'status' => 'pending',
            ]
        );
    }

    public static function findBetween(int $userA, int $userB): ?self
    {
        return self::where(function ($q) use ($userA, $userB) {
            $q->where('user_id', $userA)
              ->where('partner_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('user_id', $userB)
              ->where('partner_id', $userA);
        })->first();
    }
}
