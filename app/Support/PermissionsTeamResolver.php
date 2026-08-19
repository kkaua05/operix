<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver as PermissionsTeamResolverContract;

/**
 * Scopes spatie/laravel-permission roles/permissions to the current tenant
 * by default, defaulting to CurrentCompany rather than requiring every
 * caller to set the team id explicitly. An explicit setPermissionsTeamId()
 * call (e.g. when a super admin manages another company) still overrides it.
 */
class PermissionsTeamResolver implements PermissionsTeamResolverContract
{
    protected int|string|null $teamId = null;

    protected bool $explicit = false;

    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
        $this->explicit = true;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->explicit) {
            return $this->teamId;
        }

        return CurrentCompany::id();
    }
}
