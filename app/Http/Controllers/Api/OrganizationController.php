<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

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
        
        return response()->json([
            'success' => true,
            'data' => OrganizationResource::collection($organizations),
        ]);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);
        $organization = $this->organizationService->createOrganization($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($organization),
            'message' => 'Organization created successfully.',
        ], 201);
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);
        
        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($organization),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);
        $updatedOrganization = $this->organizationService->updateOrganization($organization, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($updatedOrganization),
            'message' => 'Organization updated successfully.',
        ]);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);
        $this->organizationService->deleteOrganization($organization);
        
        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * Toggle organization status.
     */
    public function toggleStatus(Organization $organization)
    {
        $this->authorize('update', $organization);
        
        $organization->is_active = !$organization->is_active;
        $organization->save();
        
        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($organization),
            'message' => 'Organization status updated successfully.',
        ]);
    }
}
