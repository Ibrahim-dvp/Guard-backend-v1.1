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
            'client_info' => $this->client_info,
            'status' => $this->status,
            'assignedTo' => new UserResource($this->whenLoaded('assignedTo')),
            'assignedBy' => new UserResource($this->whenLoaded('assignedBy')),
            'source' => $this->source,
            'revenue' => $this->revenue,
            // 'notes' => NoteResource::collection($this->whenLoaded('notes')),
            // 'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
