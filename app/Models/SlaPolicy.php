<?php

namespace App\Models;

use App\Enums\WorkOrderPriority;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'priority',
        'response_time_minutes',
        'resolution_time_minutes',
        'business_hours_only',
    ];

    protected function casts(): array
    {
        return [
            'priority' => WorkOrderPriority::class,
            'business_hours_only' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<WorkOrder, $this>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
