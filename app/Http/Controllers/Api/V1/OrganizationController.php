<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\SuccessResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;

class OrganizationController extends Controller
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function index()
    {
        $this->authorize('viewAny', Organization::class);
        $organizations = $this->organizationService->getAllOrganizations();
        return new SuccessResource(OrganizationResource::collection($organizations));
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);
        $organization = $this->organizationService->createOrganization($request->validated());
        return new SuccessResource(new OrganizationResource($organization), 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);
        return new SuccessResource(new OrganizationResource($organization));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);
        $updatedOrganization = $this->organizationService->updateOrganization($organization, $request->validated());
        return new SuccessResource(new OrganizationResource($updatedOrganization), 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);
        $this->organizationService->deleteOrganization($organization);
        return response()->json(null, 204);
    }
}
