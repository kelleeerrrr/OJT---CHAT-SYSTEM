<?php

namespace App\Providers;

use App\Models\User;
use App\Services\BadWordFilter;
use App\Services\MessageSanitizer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons so the word list is loaded once
        $this->app->singleton(BadWordFilter::class);
        $this->app->singleton(MessageSanitizer::class);
    }

    public function boot(): void
    {
        /**
         * Gate: manage-chat
         * Used by admin routes to verify the user has admin or superadmin role.
         */
        Gate::define('manage-chat', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-users', function (User $user) {
            return $user->isSuperAdmin();
        });

        /**
         * Gate: deny-user-chat
         * Admins can deny regular users; only superadmin can deny other admins.
         */
        Gate::define('deny-user-chat', function (User $auth, User $target) {
            if ($auth->isSuperAdmin()) {
                return $auth->id !== $target->id;
            }
            return $auth->isAdmin() && ! $target->isAdmin();
        });
    }
}
