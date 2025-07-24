<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\OrganizationRepositoryInterface;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OrganizationService
{
    protected $organizationRepository;

    public function __construct(OrganizationRepositoryInterface $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function getAllOrganizations()
    {
        return $this->organizationRepository->getAll(Auth::user());
    }

    public function createOrganization(array $details): Organization
    {
        $organization = $this->organizationRepository->create($details);
        $this->assignDirectorToOrganization($organization, $details);
        return $organization;
    }

    public function updateOrganization(Organization $organization, array $details): Organization
    {
        $organization = $this->organizationRepository->update($organization, $details);
        $this->assignDirectorToOrganization($organization, $details);
        return $organization;
    }

    public function deleteOrganization(Organization $organization): bool
    {
        // Add validation logic here later (e.g., check for child orgs/users)
        return $this->organizationRepository->delete($organization);
    }

    private function assignDirectorToOrganization(Organization $organization, array $details): void
    {
        if (isset($details['director_id'])) {
            $director = User::find($details['director_id']);
            if ($director && $director->organization_id !== $organization->id) {
                $director->organization_id = $organization->id;
                $director->save();
            }
        }
    }
}
