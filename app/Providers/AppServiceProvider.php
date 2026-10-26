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
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (method_exists($user, 'hasPermission')) {
                $action = $arguments[0] ?? null;

                return $user->hasPermission($ability, $action) ?: null;
            }

            return null;
        });
    }
}
