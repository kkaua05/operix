<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * Mirrors the DB column default (see WorkOrder for why this matters:
     * without it, a freshly-created instance has `status` null in PHP
     * memory until a fresh() reload, even though the DB already applied
     * its own default on INSERT).
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected $fillable = [
        'company_id',
        'work_order_id',
        'technician_id',
        'team_id',
        'scheduled_start',
        'scheduled_end',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
