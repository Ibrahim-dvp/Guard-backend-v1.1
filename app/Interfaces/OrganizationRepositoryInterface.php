<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface
{
    public function getAll(User $currentUser): Collection;

    public function getById(string $id): ?Organization;

    public function create(array $details): Organization;

    public function update(Organization $organization, array $newDetails): Organization;

    public function delete(Organization $organization): bool;
}
