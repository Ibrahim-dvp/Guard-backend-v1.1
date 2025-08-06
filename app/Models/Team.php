<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * Get the users associated with the team.
     */
    protected $fillable = [
        'name',
        'description',
        'slug',
        'creator_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'teams_users');
    }
    // Additional methods and properties can be added here as needed

}
