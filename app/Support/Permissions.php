<?php

namespace App\Support;

/**
 * Canonical list of granular permissions (spec §10) and the default
 * role → permissions mapping used to bootstrap a new company's RBAC
 * (see App\Actions\SeedDefaultCompanyRoles). Permissions are global
 * records in spatie/laravel-permission; roles are per-tenant.
 */
class Permissions
{
    public const ALL = [
        'customers.view',
        'customers.create',
        'customers.update',
        'customers.delete',

        'technicians.view',
        'technicians.manage',

        'teams.view',
        'teams.manage',

        'equipment.view',
        'equipment.manage',

        'work_orders.view',
        'work_orders.create',
        'work_orders.update',
        'work_orders.delete',
        'work_orders.assign',
        'work_orders.start',
        'work_orders.complete',

        'scheduling.view',
        'scheduling.manage',

        'dispatch.view',
        'dispatch.manage',

        'inventory.view',
        'inventory.manage',

        'financial.view',
        'financial.manage',

        'reports.view',

        'automations.manage',

        'audit.view',

        'settings.manage',
        'users.manage',

        'ixc.view',
    ];

    /**
     * Default permission set per company-scoped role (App\Enums\UserRole
     * values, excluding SuperAdmin — that one is a platform-wide flag on
     * the user, not a tenant-scoped spatie/permission role; see
     * User::is_super_admin and AppServiceProvider's Gate::before).
     *
     * @var array<string, array<int, string>>
     */
    public const ROLE_PERMISSIONS = [
        'admin' => self::ALL,

        'manager' => [
            'customers.view', 'customers.create', 'customers.update',
            'technicians.view', 'teams.view',
            'equipment.view', 'equipment.manage',
            'work_orders.view', 'work_orders.create', 'work_orders.update',
            'work_orders.assign', 'work_orders.start', 'work_orders.complete',
            'scheduling.view', 'scheduling.manage',
            'dispatch.view', 'dispatch.manage',
            'inventory.view',
            'financial.view',
            'reports.view',
            'audit.view',
            'ixc.view',
        ],

        'dispatcher' => [
            'customers.view', 'technicians.view', 'teams.view', 'equipment.view',
            'work_orders.view', 'work_orders.update', 'work_orders.assign',
            'scheduling.view', 'scheduling.manage',
            'dispatch.view', 'dispatch.manage',
            'ixc.view',
        ],

        'technician' => [
            'customers.view', 'equipment.view',
            'work_orders.view', 'work_orders.update', 'work_orders.start', 'work_orders.complete',
        ],

        'financial' => [
            'customers.view',
            'work_orders.view',
            'financial.view', 'financial.manage',
            'reports.view',
        ],

        'support' => [
            'customers.view', 'customers.create', 'customers.update',
            'work_orders.view', 'work_orders.create',
            'reports.view',
        ],
    ];
}
