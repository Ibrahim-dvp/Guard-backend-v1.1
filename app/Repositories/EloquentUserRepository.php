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
        if ($currentUser->hasRole(['Admin', 'Group Director'])) {
            return User::with(['roles', 'organization'])->get();
        }

        if ($currentUser->organization) {
            return User::where('organization_id', $currentUser->organization_id)
                ->with(['roles', 'organization'])
                ->get();
        }

        // If user has no organization, they can only see themselves.
        return new Collection([$currentUser->loadMissing(['roles', 'organization'])]);
    }

    public function getUserById(string $userId): ?User
    {
        return User::with(['roles', 'organization'])->find($userId);
    }

    public function createUser(array $userDetails): User
    {
        $user = User::create($userDetails);
        return $user->load(['roles', 'organization']);
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
