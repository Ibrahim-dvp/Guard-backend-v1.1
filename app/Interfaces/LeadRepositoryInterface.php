<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeadRepositoryInterface
{
    public function getAll(User $currentUser, array $filters): LengthAwarePaginator;

    public function getById(string $id): ?Lead;

    public function create(array $details): Lead;

    public function update(Lead $lead, array $newDetails): Lead;

    public function delete(Lead $lead): bool;

    public function assign(Lead $lead, User $assignee, User $assigner, string $newStatus): Lead;

    public function updateStatus(Lead $lead, string $status): Lead;
}
