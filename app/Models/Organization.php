<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCamelCaseAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;
    use HasUuids;
    use HasCamelCaseAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'director_id',
        'is_active',
    ];

    /**
     * Get the parent organization.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    /**
     * Get the child organizations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    /**
     * Get the director of the organization.
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Get the teams that belong to the organization.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get only active teams with active users.
     */
    public function activeTeams(): HasMany
    {
        return $this->teams()->whereHas('users', function ($query) {
            $query->where('users.is_active', true);
        });
    }

    /**
     * Get the users in the organization.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
