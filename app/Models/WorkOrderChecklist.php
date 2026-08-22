<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'name',
        'is_required',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return HasMany<WorkOrderChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderChecklistItem::class);
    }
}
