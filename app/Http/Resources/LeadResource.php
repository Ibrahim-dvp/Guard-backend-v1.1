<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'referralId' => $this->referral_id,
            'clientInfo' => [
                'id' => $this->id,
                'firstName' => $this->client_first_name,
                'lastName' => $this->client_last_name,
                'email' => $this->client_email,
                'phone' => $this->client_phone,
                'company' => $this->client_company,
                'address' => null, // Will need to add these fields if required
                'city' => null,
                'postalCode' => null,
                'country' => 'USA',
                'dateOfBirth' => null,
                'additionalInfo' => null,
            ],
            'status' => $this->status,
            'assignedTo' => $this->assigned_to_id,
            'assignedBy' => $this->assigned_by_id,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'notes' => [], // Will implement when notes are available
            'appointments' => [], // Will implement when appointments relationship is ready
            'revenue' => $this->revenue ?? 0,
            'source' => $this->source ?? 'Website',
        ];
    }
}
