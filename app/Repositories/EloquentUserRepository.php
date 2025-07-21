<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getAllUsers(User $currentUser): Collection
    {
        if ($currentUser->hasRole(['Admin', 'Group Director', 'Partner Director'])) {
            return User::with(['roles', 'organization'])->get();
        }

        // For now, other roles can only see themselves.
        // We can expand this later for Sales Managers to see their team.
        return new Collection([$currentUser->load(['roles', 'organization'])]);
    }

    public function getUserById(string $userId): ?User
    {
        return User::with(['roles', 'organization'])->find($userId);
    }

    public function createUser(array $userDetails): User
    {
        return User::create($userDetails);
    }

    public function updateUser(User $user, array $newDetails): User
    {
        $user->update($newDetails);
        return $user->fresh(['roles', 'organization']); // Return a fresh instance with relations
    }

    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }
}
