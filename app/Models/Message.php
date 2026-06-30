<?php

namespace App\Models;

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

    public static function conversationBetween(int $userA, int $userB)
    {
        return static::with([
                'sender:id,name,email',
                'receiver:id,name,email',
            ])
            ->where(function ($query) use ($userA, $userB) {
                $query->where(function ($q) use ($userA, $userB) {
                    $q->where('sender_id', $userA)
                      ->where('receiver_id', $userB);
                })
                ->orWhere(function ($q) use ($userA, $userB) {
                    $q->where('sender_id', $userB)
                      ->where('receiver_id', $userA);
                });
            })
            ->where('is_deleted', false)
            ->orderBy('id', 'asc'); // IMPORTANT FIX
    }
}