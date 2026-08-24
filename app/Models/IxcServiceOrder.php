<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service order/appointment synced from the IXC Provedor admin panel
 * (no API on this account — app/Services/Ixc/IxcSyncService). Read-only
 * from Operix's side: this table mirrors what IXC has, it's never the
 * source of truth for anything written back.
 */
class IxcServiceOrder extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'external_id',
        'branch',
        'customer_name',
        'subject',
        'address',
        'technician_name',
        'status',
        'scheduled_start',
        'scheduled_end',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
