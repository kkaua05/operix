<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves who should receive company-wide operational notifications
 * (§37) — currently "management" (admin + manager), the roles with
 * visibility over the whole operation rather than a single OS.
 */
class NotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function management(int $companyId): Collection
    {
        // Deliberately avoids spatie's role() scope: it resolves each role
        // name via Role::findByName() and throws RoleDoesNotExist if the
        // role hasn't been seeded yet for this guard/team — a real
        // possibility for a company created outside the normal onboarding
        // flow (e.g. a service-level test). Matching directly against the
        // pivot's role name is just as correct and never throws.
        return User::query()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
            ->get();
    }
}
