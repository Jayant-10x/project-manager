<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;

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

        $this->registerLivewireValidationToaster();
    }

    /**
     * Dispatch user-friendly toast notifications when Livewire form submissions fail validation.
     */
    protected function registerLivewireValidationToaster(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        \Livewire\on('call', function ($component, $method, $params) {
            app()->instance('livewire.current_call_method', $method);

            return function () {
                app()->forgetInstance('livewire.current_call_method');
            };
        });

        \Livewire\on('exception', function ($target, $e, $stopPropagation) {
            if ($target instanceof Component && $e instanceof ValidationException) {
                $currentMethod = app()->bound('livewire.current_call_method')
                    ? app('livewire.current_call_method')
                    : null;

                // Only dispatch toast on deliberate form submissions / user action calls (not real-time property typing)
                if (! $currentMethod || str_starts_with($currentMethod, 'updated') || str_starts_with($currentMethod, 'updating')) {
                    return;
                }

                $errors = $e->validator->errors();
                $firstError = $errors->first();
                $totalErrors = count($errors->all());

                if (! $firstError) {
                    return;
                }

                $message = $totalErrors > 1
                    ? "Form not submitted: {$firstError} (and ".($totalErrors - 1).' other '.(($totalErrors - 1) === 1 ? 'error' : 'errors').')'
                    : "Form not submitted: {$firstError}";

                $target->dispatch('toast', [
                    'msg' => $message,
                    'type' => 'err',
                ]);
            }
        });
    }
}
