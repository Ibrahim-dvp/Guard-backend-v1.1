<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'is_active' => $this->is_active,
            'parent' => new OrganizationResource($this->whenLoaded('parent')),
            'director' => new UserResource($this->whenLoaded('director')),
            'children' => OrganizationResource::collection($this->whenLoaded('children')),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
            'teams_count' => $this->when(
                $this->relationLoaded('teams'),
                fn () => $this->teams->count()
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
