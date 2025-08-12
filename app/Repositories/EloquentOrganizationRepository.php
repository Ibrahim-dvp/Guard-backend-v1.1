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
            // More efficient: single query with eager loading
            return Organization::select(['id', 'name', 'parent_id', 'director_id', 'is_active', 'created_at', 'updated_at'])
                ->with([
                    'director' => function ($query) {
                        $query->select(['id', 'name', 'email']);
                    },
                    'parent' => function ($query) {
                        $query->select(['id', 'name']);
                    }
                ])
                ->get();
        }

        if ($currentUser->organization) {
            // More efficient: load specific columns and use closure-based eager loading
            $organization = Organization::select(['id', 'name', 'parent_id', 'director_id', 'is_active', 'created_at', 'updated_at'])
                ->with([
                    'director' => function ($query) {
                        $query->select(['id', 'name', 'email']);
                    },
                    'parent' => function ($query) {
                        $query->select(['id', 'name']);
                    }
                ])
                ->find($currentUser->organization_id);

            return $organization ? new Collection([$organization]) : new Collection();
        }

        return new Collection();
    }

    public function getById(string $id): ?Organization
    {
        return Organization::select(['id', 'name', 'parent_id', 'director_id', 'is_active', 'created_at', 'updated_at'])
            ->with([
                'director' => function ($query) {
                    $query->select(['id', 'name', 'email']);
                },
                'parent' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'children' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                }
            ])
            ->find($id);
    }

    public function create(array $details): Organization
    {
        return Organization::create($details);
    }

    public function update(Organization $organization, array $newDetails): Organization
    {
        $organization->update($newDetails);
        return $organization->fresh([
            'director' => function ($query) {
                $query->select(['id', 'name', 'email']);
            },
            'parent' => function ($query) {
                $query->select(['id', 'name']);
            }
        ]);
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }
}
