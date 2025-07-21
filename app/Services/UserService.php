<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers()
    {
        return $this->userRepository->getAllUsers(Auth::user());
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->createUser($data);
        $user->assignRole($data['role_name']);
        return $user;
    }

    public function getUserById(string $id)
    {
        return $this->userRepository->getUserById($id);
    }

    public function updateUser(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->userRepository->updateUser($user, $data);
    }

    public function deleteUser(User $user)
    {
        return $this->userRepository->deleteUser($user);
    }
}
