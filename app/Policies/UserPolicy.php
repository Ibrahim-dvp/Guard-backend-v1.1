<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null; // let other methods decide
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // A user can view their own profile.
        if ($user->id === $model->id) {
            return true;
        }

        // A user with the general permission can view others.
        // More specific logic (like managers viewing their team) will be added here later.
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // A user can update their own profile.
        if ($user->id === $model->id) {
            return true;
        }

        // A user with the permission can update other users.
        return $user->can('users.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // A user can delete their own account.
        if ($user->id === $model->id) {
            return true;
        }

        // A user with the permission can delete other users.
        return $user->can('users.delete');
    }
}
