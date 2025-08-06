<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Requests\TeamMemberRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\SuccessResource;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);
        $teams = $this->teamService->getTeams($request->all());
        return new SuccessResource($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $this->authorize('create', Team::class);
        $team = $this->teamService->createTeam($request->validated());
        return new SuccessResource(new TeamResource($team), 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);
        $team = $this->teamService->getTeamById($team->id);
        return new SuccessResource(new TeamResource($team));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $this->authorize('update', $team);
        $updatedTeam = $this->teamService->updateTeam($team, $request->validated());
        return new SuccessResource(new TeamResource($updatedTeam), 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);
        $this->teamService->deleteTeam($team);
        return new SuccessResource(null, 'Team deleted successfully.');
    }

    /**
     * Add a user to the team.
     */
    public function addUser(TeamMemberRequest $request, Team $team)
    {
        $this->authorize('manageMembers', $team);
        $success = $this->teamService->addUserToTeam($team, $request->validated('user_id'));
        
        if ($success) {
            return new SuccessResource(null, 'User added to team successfully.');
        }
        
        return response()->json(['message' => 'User is already a member of this team.'], 200);
    }

    /**
     * Remove a user from the team.
     */
    public function removeUser(Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);
        $success = $this->teamService->removeUserFromTeam($team, $user->id);
        
        if ($success) {
            return new SuccessResource(null, 'User removed from team successfully.');
        }
        
        return response()->json(['message' => 'User is not a member of this team.'], 200);
    }

    /**
     * Get all users in the team.
     */
    public function getTeamUsers(Team $team)
    {
        $this->authorize('view', $team);
        $users = $this->teamService->getTeamUsers($team);
        return new SuccessResource(UserResource::collection($users));
    }

    /**
     * Get all teams for a specific user.
     */
    public function getUserTeams(User $user )
    {
        $teams = $this->teamService->getUserTeams($user->id);
        return new SuccessResource(TeamResource::collection($teams));
    }
}
