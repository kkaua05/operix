<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single entry point for writing to audit_logs (§39). Deliberately not a
 * blanket observer on every model — only the handful of genuinely
 * sensitive actions (auth, user/role management, money, deletions,
 * integration settings) call this explicitly, so the log stays a
 * meaningful trail instead of noise.
 */
class AuditService
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
        ?int $companyId = null,
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
