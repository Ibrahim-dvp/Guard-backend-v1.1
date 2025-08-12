<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TeamRepositoryInterface
{
    public function getAll(User $currentUser, array $filters): LengthAwarePaginator;

    public function getById(string $id): ?Team;

    public function create(array $details): Team;

    public function update(Team $team, array $newDetails): Team;

    public function delete(Team $team): bool;

    public function addUserToTeam(Team $team, User $user): bool;

    public function removeUserFromTeam(Team $team, User $user): bool;

    public function getTeamUsers(Team $team): Collection;

    public function getUserTeams(User $user): Collection;

    public function getByOrganization(string $organizationId, array $filters = []): LengthAwarePaginator;

    public function getTeamWithUsers(string $id): ?Team;
}
