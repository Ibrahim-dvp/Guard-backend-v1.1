<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function getAllUsers(User $currentUser): Collection;

    public function getUserById(string $userId): ?User;

    public function createUser(array $userDetails): User;

    public function updateUser(User $user, array $newDetails): User;

    public function deleteUser(User $user): bool;
}
