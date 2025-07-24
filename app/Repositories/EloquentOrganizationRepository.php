<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\OrganizationRepositoryInterface;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    public function getAll(User $currentUser): Collection
    {
        if ($currentUser->hasRole(['Admin', 'Group Director'])) {
            return Organization::with(['director', 'parent'])->get();
        }

        if ($currentUser->organization) {
            // A Partner Director might see their org and its children.
            // For now, all other roles just see their own organization.
            return new Collection([$currentUser->organization->load(['director', 'parent'])]);
        }

        return new Collection();
    }

    public function getById(string $id): ?Organization
    {
        return Organization::find($id);
    }

    public function create(array $details): Organization
    {
        return Organization::create($details);
    }

    public function update(Organization $organization, array $newDetails): Organization
    {
        $organization->update($newDetails);
        return $organization->fresh(['director', 'parent']);
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }
}
