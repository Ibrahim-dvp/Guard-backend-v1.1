<?php

namespace App\Models;

use App\Traits\HasCamelCaseAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, HasUuids, HasCamelCaseAttributes;

    protected $fillable = [
        'lead_id',
        'scheduled_by',
        'scheduled_at',
        'duration',
        'location',
        'notes',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
