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
            'scheduled_at' => $this->scheduled_at?->toDateTimeString(),
            'duration' => $this->duration,
            'location' => $this->location,
            'notes' => $this->notes,
            'status' => $this->status,
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'scheduled_by' => new UserResource($this->whenLoaded('scheduledBy')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
