<?php

namespace App\Models\Concerns;

use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scopes a model to the current tenant (CurrentCompany) and auto-fills
 * company_id on creation. This is the multi-tenancy enforcement mechanism
 * required by the spec (§8): a user must never see another company's data.
 *
 * When CurrentCompany is unset (console, guests, or a super admin operating
 * without a fixed tenant), the scope is a no-op — callers relying on that
 * must already be authorized elsewhere (e.g. a SUPER_ADMIN-only policy).
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if ($companyId = CurrentCompany::id()) {
                $builder->where($builder->getModel()->qualifyColumn('company_id'), $companyId);
            }
        });

        static::creating(function ($model) {
            if (! $model->company_id && $companyId = CurrentCompany::id()) {
                $model->company_id = $companyId;
            }
        });
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
