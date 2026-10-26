<?php

namespace App\Constants;

class Permissions
{
    /**
     * Multi-level Role & Module Permissions Matrix.
     * Structure: [role_name => [module_name => [action => bool]]]
     *
     * When the permission manager module is introduced later,
     * this multi-level hierarchy will be loaded and updated dynamically.
     *
     * @var array<string, array<string, array<string, bool>>>
     */
    public const array ROLE_PERMISSIONS = [
        'Admin' => [
            'users' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'team' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'customers' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'projects' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'schedules' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'payments' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
            ],
            'today' => [
                'view' => true,
            ],
            'reports' => [
                'view' => true,
                'export' => true,
            ],
            'settings' => [
                'view' => true,
                'edit' => true,
            ],
        ],
        'Manager' => [
            'users' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'team' => [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'customers' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'projects' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'schedules' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'payments' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'today' => [
                'view' => true,
            ],
            'reports' => [
                'view' => true,
                'export' => true,
            ],
            'settings' => [
                'view' => false,
                'edit' => false,
            ],
        ],
        'Coordinator' => [
            'users' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'team' => [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'customers' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'projects' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'schedules' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'payments' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'today' => [
                'view' => true,
            ],
            'reports' => [
                'view' => true,
                'export' => false,
            ],
            'settings' => [
                'view' => false,
                'edit' => false,
            ],
        ],
        'Sales' => [
            'users' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'team' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'customers' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'projects' => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            'schedules' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'payments' => [
                'view' => false,
                'add' => false,
                'edit' => false,
                'delete' => false,
            ],
            'today' => [
                'view' => false,
            ],
            'reports' => [
                'view' => true,
                'export' => false,
            ],
            'settings' => [
                'view' => false,
                'edit' => false,
            ],
        ],
    ];

    /**
     * Check if a role has permission for a specific module and action.
     * Supports both two arguments can($role, 'users', 'add')
     * and dot notation can($role, 'users.add').
     */
    public static function can(?string $role, string $moduleOrAbility, ?string $action = null): bool
    {
        if (! $role || ! isset(self::ROLE_PERMISSIONS[$role])) {
            return false;
        }

        if ($action === null && str_contains($moduleOrAbility, '.')) {
            [$module, $act] = explode('.', $moduleOrAbility, 2);
        } else {
            $module = $moduleOrAbility;
            $act = $action ?? 'view';
        }

        $modulePermissions = self::ROLE_PERMISSIONS[$role][$module] ?? [];

        // Support 'add/edit' or combined actions
        if ($act === 'add/edit' || $act === 'add_edit') {
            return ($modulePermissions['add'] ?? false) || ($modulePermissions['edit'] ?? false);
        }

        return (bool) ($modulePermissions[$act] ?? false);
    }

    /**
     * Retrieve all module permissions for a given role.
     *
     * @return array<string, array<string, bool>>
     */
    public static function forRole(?string $role): array
    {
        if (! $role) {
            return [];
        }

        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}
