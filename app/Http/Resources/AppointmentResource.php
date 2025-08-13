<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
            'scheduledAt' => $this->scheduled_at?->toISOString(),
            'duration' => $this->duration,
            'location' => $this->location,
            'notes' => $this->notes,
            'status' => $this->status,
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'scheduledBy' => new UserResource($this->whenLoaded('scheduledBy')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
