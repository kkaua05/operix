<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * Mirrors the DB column defaults so a freshly-created instance already
     * has these set in PHP memory — otherwise Eloquent leaves them null
     * until a fresh() reload, since MySQL applies its DEFAULT silently on
     * INSERT without Eloquent ever seeing the value it chose.
     */
    protected $attributes = [
        'priority' => 'medium',
        'status' => 'new',
        'origin' => 'manual',
        'sla_status' => 'normal',
    ];

    protected $fillable = [
        'company_id',
        'number',
        'customer_id',
        'customer_address_id',
        'equipment_id',
        'technician_id',
        'team_id',
        'sla_policy_id',
        'created_by',
        'category',
        'subcategory',
        'description',
        'priority',
        'status',
        'origin',
        'scheduled_at',
        'started_at',
        'completed_at',
        'estimated_duration_minutes',
        'sla_due_at',
        'sla_status',
        'diagnosis_category',
        'diagnosis',
        'cause',
        'resolution',
        'recommendation',
        'notes',
        'arrived_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => WorkOrderPriority::class,
            'status' => WorkOrderStatus::class,
            'sla_status' => SlaStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'arrived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<CustomerAddress, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<WorkOrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(WorkOrderStatusHistory::class);
    }

    /**
     * @return HasMany<WorkOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /**
     * @return HasMany<WorkOrderAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    /**
     * @return HasMany<WorkOrderChecklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(WorkOrderChecklist::class);
    }

    /**
     * @return HasMany<WorkOrderMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }

    /**
     * @return HasMany<WorkOrderService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(WorkOrderService::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<Dispatch, $this>
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    /**
     * @return HasMany<SlaEvent, $this>
     */
    public function slaEvents(): HasMany
    {
        return $this->hasMany(SlaEvent::class);
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * @return HasOne<Rating, $this>
     */
    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }
}
