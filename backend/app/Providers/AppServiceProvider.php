<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', function ($user) {
            return $user->is_superadmin;
        });

        Gate::define('manageRoles', function ($user) {
            return $user->is_superadmin || $user->hasAnyPermission(['roles.view', 'roles.create', 'roles.update', 'roles.delete']);
        });
    }
}
