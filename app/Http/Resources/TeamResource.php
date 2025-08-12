<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'description' => $this->description,
            'slug' => $this->slug,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'creator_id' => $this->creator_id,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'organization_id' => $this->organization_id,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'users_count' => $this->when(
                $this->relationLoaded('users'),
                fn () => $this->users->count()
            ),
            'active_users_count' => $this->when(
                isset($this->active_users_count),
                fn () => $this->active_users_count
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
