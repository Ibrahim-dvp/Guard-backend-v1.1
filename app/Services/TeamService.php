<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamService
{
    protected $teamRepository;

    public function __construct(TeamRepositoryInterface $teamRepository)
    {
        $this->teamRepository = $teamRepository;
    }

    public function getTeams(array $filters): LengthAwarePaginator
    {
        /** @var User $user */
        $user = Auth::user();
        return $this->teamRepository->getAll($user, $filters);
    }

    public function createTeam(array $details): Team
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Set the creator
        $details['creator_id'] = $user->id;
        
        // Auto-assign organization based on user's role and organization
        if (!isset($details['organization_id'])) {
            if ($user->organization_id) {
                $details['organization_id'] = $user->organization_id;
            } else {
                throw ValidationException::withMessages([
                    'organization_id' => 'Teams must be associated with an organization.',
                ]);
            }
        } else {
            // Validate user can create teams in the specified organization
            $this->validateOrganizationAccess($user, $details['organization_id']);
        }
        
        // Generate slug from name if not provided
        if (!isset($details['slug']) || empty($details['slug'])) {
            $details['slug'] = Str::slug($details['name']);
            
            // Ensure slug uniqueness within the organization
            $counter = 1;
            $originalSlug = $details['slug'];
            while (Team::where('slug', $details['slug'])
                      ->where('organization_id', $details['organization_id'])
                      ->exists()) {
                $details['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        return $this->teamRepository->create($details);
    }

    public function updateTeam(Team $team, array $details): Team
    {
        $this->authorizeTeamAction($team, 'update');

        // Update slug if name is changed and slug is not explicitly provided
        if (isset($details['name']) && (!isset($details['slug']) || empty($details['slug']))) {
            $newSlug = Str::slug($details['name']);
            
            // Ensure slug uniqueness (excluding current team)
            $counter = 1;
            $originalSlug = $newSlug;
            while (Team::where('slug', $newSlug)->where('id', '!=', $team->id)->exists()) {
                $newSlug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $details['slug'] = $newSlug;
        }

        return $this->teamRepository->update($team, $details);
    }

    public function getTeamById(string $id): ?Team
    {
        $team = $this->teamRepository->getTeamWithUsers($id);
        
        if ($team) {
            $this->authorizeTeamAction($team, 'view');
        }
        
        return $team;
    }

    public function deleteTeam(Team $team): bool
    {
        $this->authorizeTeamAction($team, 'delete');
        return $this->teamRepository->delete($team);
    }

    public function addUserToTeam(Team $team, string $userId): bool
    {
        $this->authorizeTeamAction($team, 'manage_members');
        
        $user = User::findOrFail($userId);
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Business rules for adding users
        if ($currentUser->hasRole(['Group Director', 'Partner Director'])) {
            // Directors can only add users from their organization or team's organization
            if ($user->organization_id !== $currentUser->organization_id && 
                $user->organization_id !== $team->organization_id) {
                throw ValidationException::withMessages([
                    'user_id' => 'You can only add users from your organization or the team\'s organization.',
                ]);
            }
        }

        return $this->teamRepository->addUserToTeam($team, $user);
    }

    public function removeUserFromTeam(Team $team, string $userId): bool
    {
        $this->authorizeTeamAction($team, 'manage_members');
        
        $user = User::findOrFail($userId);
        return $this->teamRepository->removeUserFromTeam($team, $user);
    }

    public function getTeamUsers(Team $team): Collection
    {
        $this->authorizeTeamAction($team, 'view');
        return $this->teamRepository->getTeamUsers($team);
    }

    public function getUserTeams(string $userId ): Collection
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $user = User::findOrFail($userId);

        // Users can only see their own teams unless they have special permissions
        if ($userId && $user->id !== $authUser->id) {
            if (!$authUser->hasRole(['Group Director', 'Partner Director'])) {
                throw ValidationException::withMessages([
                    'user_id' => 'You are not authorized to view this user\'s teams.',
                ]);
            }
        }

        return $this->teamRepository->getUserTeams($user);
    }

    /**
     * Get teams by organization
     */
    public function getTeamsByOrganization(string $organizationId, array $filters = []): LengthAwarePaginator
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Validate user can access this organization's teams
        $this->validateOrganizationAccess($user, $organizationId);
        
        return $this->teamRepository->getByOrganization($organizationId, $filters);
    }

    /**
     * Validate user has access to organization
     */
    private function validateOrganizationAccess(User $user, string $organizationId): void
    {
        // Super Admin can access any organization
        if ($user->hasRole(['Admin'])) {
            return;
        }

        // Group Directors can access their organization and child organizations
        if ($user->hasRole('Group Director')) {
            if ($user->organization_id === $organizationId) {
                return;
            }
            
            // Check if target organization is a child of user's organization
            $targetOrg = \App\Models\Organization::find($organizationId);
            if ($targetOrg && $targetOrg->parent_id === $user->organization_id) {
                return;
            }
        }

        // Partner Directors can only access their own organization
        if ($user->hasRole('Partner Director')) {
            if ($user->organization_id === $organizationId) {
                return;
            }
        }

        // Other roles can only access their own organization
        if ($user->organization_id === $organizationId) {
            return;
        }

        throw ValidationException::withMessages([
            'organization' => 'You are not authorized to access teams in this organization.',
        ]);
    }

    /**
     * Authorize team actions based on user roles and team ownership
     */
    private function authorizeTeamAction(Team $team, string $action): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Super Admin can do everything
        if ($user->hasRole(['Admin'])) {
            return;
        }

        // Team creator can do everything with their team
        if ($team->creator_id === $user->id) {
            return;
        }

        // Group Directors can manage teams within their organization hierarchy
        if ($user->hasRole('Group Director')) {
            if ($team->organization_id === $user->organization_id) {
                return;
            }
            
            // Check if team's organization is a child of user's organization
            if ($team->organization && $team->organization->parent_id === $user->organization_id) {
                return;
            }
        }

        // Partner Directors can manage teams within their organization
        if ($user->hasRole('Partner Director')) {
            if ($team->organization_id === $user->organization_id) {
                return;
            }
        }

        // Coordinators can view teams within their organization
        if ($user->hasRole('Coordinator') && $action === 'view') {
            if ($team->organization_id === $user->organization_id) {
                return;
            }
        }

        // Team members can view team details
        if ($action === 'view' && $team->users()->where('user_id', $user->id)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'team' => 'You are not authorized to ' . str_replace('_', ' ', $action) . ' this team.',
        ]);
    }
}
