<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCamelCaseAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
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
        'description',
        'slug',
        'creator_id',
        'organization_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<int, string>
     */
    protected $with = [];

    /**
     * Get the organization that owns the team.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the team.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the users that belong to the team.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teams_users')
                    ->withTimestamps();
    }

    /**
     * Get only active users that belong to the team.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->where('users.is_active', true);
    }

    /**
     * Scope to filter teams by organization.
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to filter teams with user counts (performance optimization).
     */
    public function scopeWithUserCounts($query)
    {
        return $query->withCount(['users', 'activeUsers']);
    }

    /**
     * Scope to eager load common relationships (performance optimization).
     */
    public function scopeWithRelations($query, array $relations = ['creator', 'organization'])
    {
        return $query->with($relations);
    }
}
