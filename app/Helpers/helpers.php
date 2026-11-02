<?php

declare(strict_types=1);

use App\Constants\Permissions;
use App\Models\User;
use App\Services\AuditNotificationService;

if (! function_exists('logged_in_user')) {
    /**
     * Get the currently authenticated user instance.
     */
    function logged_in_user(): ?User
    {
        return auth()->user();
    }
}

if (! function_exists('logged_in_user_id')) {
    /**
     * Get the currently authenticated user ID.
     */
    function logged_in_user_id(): ?int
    {
        return auth()->id();
    }
}

if (! function_exists('has_permission')) {
    /**
     * Check if the authenticated user has permission for a specific module and action.
     * Supports both two arguments has_permission('customers', 'view') and dot notation has_permission('customers.view').
     */
    function has_permission(string $moduleOrAbility, ?string $action = null): bool
    {
        $user = logged_in_user();

        return $user ? $user->hasPermission($moduleOrAbility, $action) : false;
    }
}

if (! function_exists('is_allowed')) {
    /**
     * Check if a specific role string has permission for a module and action.
     */
    function is_allowed(?string $role, string $moduleOrAbility, ?string $action = null): bool
    {
        return Permissions::can($role, $moduleOrAbility, $action);
    }
}

if (! function_exists('is_admin')) {
    /**
     * Check if the currently authenticated user has the Admin role.
     */
    function is_admin(): bool
    {
        return logged_in_user()?->role === 'Admin';
    }
}

if (! function_exists('is_manager')) {
    /**
     * Check if the currently authenticated user has the Manager role.
     */
    function is_manager(): bool
    {
        return logged_in_user()?->role === 'Manager';
    }
}

if (! function_exists('is_coordinator')) {
    /**
     * Check if the currently authenticated user has the Coordinator role.
     */
    function is_coordinator(): bool
    {
        return logged_in_user()?->role === 'Coordinator';
    }
}

if (! function_exists('is_sales')) {
    /**
     * Check if the currently authenticated user has the Sales role.
     */
    function is_sales(): bool
    {
        return logged_in_user()?->role === 'Sales';
    }
}

if (! function_exists('is_tech_person')) {
    /**
     * Check if the currently authenticated user has the TechPerson role.
     */
    function is_tech_person(): bool
    {
        return (bool) logged_in_user()?->isTechPerson();
    }
}

if (! function_exists('current_user_downline_ids')) {
    /**
     * Get the downline user IDs for the current authenticated user.
     * Optionally exclude the current user's ID.
     */
    function current_user_downline_ids(bool $excludeSelf = false): array
    {
        $user = logged_in_user();
        if (! $user) {
            return [];
        }

        $ids = $user->getDownlineUserIds();

        return $excludeSelf ? array_values(array_diff($ids, [$user->id])) : $ids;
    }
}

if (! function_exists('status_badge_class')) {
    /**
     * Resolve the CSS badge class for a given entity status.
     */
    function status_badge_class(?string $status): string
    {
        return match ($status) {
            'Active', 'Completed', 'Approved', 'Paid' => 'b-green',
            'On Hold', 'Pending', 'In Progress', 'Partial' => 'b-amber',
            'Churned', 'Cancelled', 'Rejected', 'Overdue' => 'b-red',
            default => 'b-grey',
        };
    }
}

if (! function_exists('health_badge_class')) {
    /**
     * Resolve the CSS badge class for a given project delivery health.
     */
    function health_badge_class(?string $health): string
    {
        return match ($health) {
            'On Track' => 'b-green',
            'At Risk' => 'b-amber',
            'Delayed' => 'b-red',
            default => 'b-grey',
        };
    }
}

if (! function_exists('audit_service')) {
    /**
     * Retrieve the singleton instance of the AuditNotificationService.
     */
    function audit_service(): AuditNotificationService
    {
        return app(AuditNotificationService::class);
    }
}

if (! function_exists('format_inr')) {
    /**
     * Format a numeric amount into Indian Rupee formatting (e.g. ₹5,00,000.00 or ₹50,000).
     */
    function format_inr(float|int|string|null $amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '₹0.00';
        }

        return '₹'.number_format((float) $amount, $decimals);
    }
}
