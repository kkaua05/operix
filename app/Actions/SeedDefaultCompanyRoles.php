<?php

namespace App\Actions;

use App\Models\Company;
use App\Support\Permissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the 7 default roles (spec §10) for a company, scoped to it via
 * spatie/laravel-permission's teams feature, and syncs each role's default
 * permission set. Run once per company — on onboarding (Fase 66) and by the
 * demo seeder (Fase 26).
 */
class SeedDefaultCompanyRoles
{
    public function handle(Company $company): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($company->id);

        try {
            foreach (Permissions::ROLE_PERMISSIONS as $roleName => $permissions) {
                $role = Role::findOrCreate($roleName, 'web');
                $role->syncPermissions($permissions);
            }
        } finally {
            // Clear the explicit pin — back to following CurrentCompany.
            $registrar->setPermissionsTeamId(null);
        }
    }
}
