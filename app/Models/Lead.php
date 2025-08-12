<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'referral_id',
        'organization_id',
        'client_first_name',
        'client_last_name',
        'client_email',
        'client_phone',
        'client_company',
        'status',
        'assigned_to_id',
        'assigned_by_id',
        'source',
        'revenue',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => LeadStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who referred the lead.
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referral_id');
    }

    /**
     * Get the organization the lead belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user the lead is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * Get the user who assigned the lead.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /**
     * Get the notes for the lead.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the appointments for the lead.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the client's full name.
     */
    public function getClientFullNameAttribute(): string
    {
        return trim($this->client_first_name . ' ' . $this->client_last_name);
    }

    /**
     * Scope to search clients by name, email, phone, or company.
     */
    public function scopeSearchClient($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('client_first_name', 'LIKE', "%{$search}%")
              ->orWhere('client_last_name', 'LIKE', "%{$search}%")
              ->orWhere('client_email', 'LIKE', "%{$search}%")
              ->orWhere('client_phone', 'LIKE', "%{$search}%")
              ->orWhere('client_company', 'LIKE', "%{$search}%");
        });
    }
}
