<?php

use App\Support\Permissions;

/**
 * Regression net for the role → permission matrix (§10, §52): as the
 * roadmap grows and Permissions::ROLE_PERMISSIONS gets edited for new
 * modules, this catches an accidental permission gained or lost by a role
 * without anyone noticing — every other RBAC test exercises one
 * permission at a time through a Livewire component, none of them assert
 * the matrix itself stays internally consistent.
 */
test('every permission referenced by a role actually exists in the canonical permission list', function () {
    foreach (Permissions::ROLE_PERMISSIONS as $role => $permissions) {
        $unknown = array_diff($permissions, Permissions::ALL);

        expect($unknown)->toBe([], "Role \"{$role}\" grants unknown permission(s): ".implode(', ', $unknown));
    }
});

test('the admin role grants every permission in the system', function () {
    expect(Permissions::ROLE_PERMISSIONS['admin'])->toEqualCanonicalizing(Permissions::ALL);
});

test('the technician role is limited to field-execution permissions only', function () {
    $technicianPermissions = Permissions::ROLE_PERMISSIONS['technician'];

    expect($technicianPermissions)->not->toContain('users.manage')
        ->and($technicianPermissions)->not->toContain('settings.manage')
        ->and($technicianPermissions)->not->toContain('financial.manage')
        ->and($technicianPermissions)->not->toContain('customers.delete')
        ->and($technicianPermissions)->toContain('work_orders.start')
        ->and($technicianPermissions)->toContain('work_orders.complete');
});

test('every non-admin role is a strict subset of what admin can do', function () {
    foreach (Permissions::ROLE_PERMISSIONS as $role => $permissions) {
        if ($role === 'admin') {
            continue;
        }

        $extra = array_diff($permissions, Permissions::ROLE_PERMISSIONS['admin']);

        expect($extra)->toBe([], "Role \"{$role}\" grants permissions admin does not have: ".implode(', ', $extra));
    }
});

test('a user\'s effective permissions via can() exactly match their role\'s declared permissions', function () {
    $user = actingAsCompanyUser(['financial']);

    foreach (Permissions::ALL as $permission) {
        $expected = in_array($permission, Permissions::ROLE_PERMISSIONS['financial'], true);

        expect($user->can($permission))->toBe($expected, "Permission \"{$permission}\" mismatch for role \"financial\".");
    }
});
