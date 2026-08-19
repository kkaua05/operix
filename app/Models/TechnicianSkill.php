<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'skill',
        'proficiency_level',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
