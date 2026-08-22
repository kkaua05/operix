<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'trading_name',
        'document',
        'email',
        'phone',
        'timezone',
        'locale',
        'currency',
        'status',
        'trial_ends_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * The company's configured outbound webhook URL (§37), stored in the
     * free-form settings JSON column rather than a dedicated migration —
     * this is the only integration setting the app has so far.
     */
    public function webhookUrl(): ?string
    {
        return $this->settings['webhook_url'] ?? null;
    }
}
