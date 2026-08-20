<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'work_order_status_history';

    protected $fillable = [
        'work_order_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => WorkOrderStatus::class,
            'to_status' => WorkOrderStatus::class,
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
