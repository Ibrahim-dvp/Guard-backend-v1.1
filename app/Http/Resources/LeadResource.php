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
            'referral' => new UserResource($this->whenLoaded('referral')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'client' => [
                'first_name' => $this->client_first_name,
                'last_name' => $this->client_last_name,
                'full_name' => $this->client_full_name,
                'email' => $this->client_email,
                'phone' => $this->client_phone,
                'company' => $this->client_company,
            ],
            'status' => $this->status,
            'assignedTo' => new UserResource($this->whenLoaded('assignedTo')),
            'assignedBy' => new UserResource($this->whenLoaded('assignedBy')),
            'source' => $this->source,
            'revenue' => $this->revenue,
            // 'notes' => NoteResource::collection($this->whenLoaded('notes')),
            // 'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
