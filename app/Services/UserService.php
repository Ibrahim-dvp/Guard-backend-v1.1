<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
        $data['created_by'] = Auth::id(); // Set the creator
        
        // Handle Referral users - assign them to Protecta Group organization
        if ($data['role_name'] === 'Referral') {
            $protectaOrg = Organization::where('name', 'Protecta Group')->first();
            if ($protectaOrg) {
                $data['organization_id'] = $protectaOrg->id;
            } 
        }

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

        $user = $this->userRepository->updateUser($user, $data);

        if (isset($data['role_name'])) {
            $user->syncRoles([$data['role_name']]);
        }

        return $user;
    }

    public function deleteUser(User $user)
    {
        return $this->userRepository->deleteUser($user);
    }
}
