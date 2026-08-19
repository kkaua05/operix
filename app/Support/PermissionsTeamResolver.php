<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver as PermissionsTeamResolverContract;

/**
 * Scopes spatie/laravel-permission roles/permissions to the current tenant
 * by default, defaulting to CurrentCompany rather than requiring every
 * caller to set the team id explicitly.
 *
 * setPermissionsTeamId($id) pins an explicit override (e.g. a super admin
 * temporarily managing another company's roles); setPermissionsTeamId(null)
 * clears the override and goes back to following CurrentCompany dynamically
 * — it does NOT pin to "no team", since that would permanently disable
 * tenant scoping for the rest of the process once called.
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
        $this->explicit = $id !== null;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->explicit) {
            return $this->teamId;
        }

        return CurrentCompany::id();
    }
}
