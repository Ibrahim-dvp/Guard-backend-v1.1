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
        
        // Generate slug from name if not provided
        if (!isset($details['slug']) || empty($details['slug'])) {
            $details['slug'] = Str::slug($details['name']);
            
            // Ensure slug uniqueness
            $counter = 1;
            $originalSlug = $details['slug'];
            while (Team::where('slug', $details['slug'])->exists()) {
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
        $team = $this->teamRepository->getById($id);
        
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
        if ($currentUser->hasRole('Director')) {
            // Directors can only add users from their organization
            if ($user->organization_id !== $currentUser->organization_id) {
                throw ValidationException::withMessages([
                    'user_id' => 'You can only add users from your own organization to the team.',
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
            if (!$authUser->hasRole(['Super Admin', 'Director'])) {
                throw ValidationException::withMessages([
                    'user_id' => 'You are not authorized to view this user\'s teams.',
                ]);
            }
        }

        return $this->teamRepository->getUserTeams($user);
    }

    /**
     * Authorize team actions based on user roles and team ownership
     */
    private function authorizeTeamAction(Team $team, string $action): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Super Admin can do everything
        if ($user->hasRole('Super Admin')) {
            return;
        }

        // Team creator can do everything with their team
        if ($team->creator_id === $user->id) {
            return;
        }

        // Directors can manage teams within their organization
        if ($user->hasRole('Director')) {
            if ($team->creator->organization_id === $user->organization_id) {
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
