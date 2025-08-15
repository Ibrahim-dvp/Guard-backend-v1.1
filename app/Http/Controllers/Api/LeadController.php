<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Http\Resources\SuccessResource;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    protected $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);
        
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        
        $leads = $this->leadService->getLeads($request->all());
        
        // Manual pagination for consistent API response
        $total = $leads->count();
        $totalPages = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;
        $paginatedLeads = $leads->slice($offset, $pageSize)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => LeadResource::collection($paginatedLeads),
                'total' => $total,
                'page' => (int) $page,
                'pageSize' => (int) $pageSize,
                'totalPages' => $totalPages,
            ],
            'message' => 'Success',
        ]);
    }

    public function store(StoreLeadRequest $request)
    {
        $this->authorize('create', Lead::class);
        $lead = $this->leadService->createLead($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new LeadResource($lead),
            'message' => 'Lead created successfully.',
        ], 201);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        
        return response()->json([
            'success' => true,
            'data' => new LeadResource($lead),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $updatedLead = $this->leadService->updateLead($lead, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new LeadResource($updatedLead),
            'message' => 'Lead updated successfully.',
        ]);
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        
        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorize('assign', $lead);
        $assignedLead = $this->leadService->assignLead($lead, $request->input('assignedTo'));
        
        return response()->json([
            'success' => true,
            'data' => new LeadResource($assignedLead),
            'message' => 'Lead assigned successfully.',
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead)
    {
        $this->authorize('updateStatus', $lead);
        $updatedLead = $this->leadService->updateLeadStatus($lead, $request->validated()['status']);
        
        return response()->json([
            'success' => true,
            'data' => new LeadResource($updatedLead),
            'message' => 'Lead status updated successfully.',
        ]);
    }

    /**
     * Get leads by status.
     */
    public function getByStatus(Request $request, string $status)
    {
        $this->authorize('viewAny', Lead::class);
        
        $user = Auth::user();
        $leadsQuery = Lead::where('status', $status);
        
        // Apply role-based filtering
        if ($user->hasRole(['Admin', 'Group Director'])) {
            // Admin and Group Director see all leads
        } elseif ($user->hasRole('Partner Director')) {
            $leadsQuery->where('organization_id', $user->organization_id);
        } elseif ($user->hasRole('Sales Manager')) {
            $leadsQuery->where(function($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhere('assigned_by_id', $user->id);
            });
        } else {
            $leadsQuery->where('assigned_to_id', $user->id);
        }
        
        $leads = $leadsQuery->get();
        
        return response()->json([
            'success' => true,
            'data' => LeadResource::collection($leads),
        ]);
    }
}
