<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Private chat channel between two users.
 *
 * Channel name: "chat.{lower_id}.{higher_id}"
 * A user may only subscribe if their ID matches one of the two participants.
 */
Broadcast::channel('chat.{userA}.{userB}', function (User $user, int $userA, int $userB) {
    // Enforce canonical ordering: lower ID is always first in the channel name
    [$lower, $higher] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

    // Verify the channel was constructed with canonical ordering
    if ($userA !== $lower || $userB !== $higher) {
        return false;
    }

    // The subscribing user must be one of the two participants
    return in_array($user->id, [$lower, $higher]);
});

/**
 * Per-user private notification channel.
 * Used for real-time "chat denied / restored" admin notifications.
 */
Broadcast::channel('user.{userId}.notifications', function (User $user, int $userId) {
    return $user->id === $userId;
});
