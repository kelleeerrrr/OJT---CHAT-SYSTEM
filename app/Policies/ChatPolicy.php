<?php

namespace App\Policies;

use App\Models\User;

class ChatPolicy
{
    /**
     * Admins and superadmins can manage chat.
     */
    public function manageChat(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Only superadmin can promote/demote admin roles.
     */
    public function manageRoles(User $authUser): bool
    {
        return $authUser->isSuperAdmin();
    }

    /**
     * Can the admin deny this target user's chat?
     * Admins can deny regular users; only superadmin can deny other admins.
     */
    public function denyChat(User $authUser, User $targetUser): bool
    {
        if ($authUser->isSuperAdmin()) {
            return $authUser->id !== $targetUser->id;
        }

        return $authUser->isAdmin() && ! $targetUser->isAdmin();
    }
}
