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
        $leads = $this->leadService->getLeads($request->all());
        return new SuccessResource($leads);
    }

    public function store(StoreLeadRequest $request)
    {
        $this->authorize('create', Lead::class);
        $lead = $this->leadService->createLead($request->validated());
        return new SuccessResource(new LeadResource($lead), 'Lead created successfully.');
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        return new SuccessResource(new LeadResource($lead));
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $updatedLead = $this->leadService->updateLead($lead, $request->validated());
        return new SuccessResource(new LeadResource($updatedLead), 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        // $this->leadService->deleteLead($lead);
        return response()->json(null, 204);
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorize('assign', $lead);
        $assignedLead = $this->leadService->assignLead($lead, $request->input('assignedTo'));
        return new SuccessResource(new LeadResource($assignedLead), 'Lead assigned successfully.');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead)
    {
        $this->authorize('updateStatus', $lead);
        $updatedLead = $this->leadService->updateLeadStatus($lead, $request->validated()['status']);
        return new SuccessResource(new LeadResource($updatedLead), 'Lead status updated successfully.');
    }
}
