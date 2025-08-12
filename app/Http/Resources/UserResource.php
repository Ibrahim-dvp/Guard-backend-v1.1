<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'role' => $this->getRoleNames()->first(), // Single role as string
            'organizationId' => $this->organization_id,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'isActive' => $this->is_active,
            'permissions' => $this->getAllPermissions()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'resource' => explode('.', $permission->name)[0] ?? 'general',
                    'action' => explode('.', $permission->name)[1] ?? 'access',
                ];
            })->toArray(),
            $this->mergeWhen($request->user() && $request->user()->id === $this->id, [
                'settings' => [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                        'sms' => false,
                    ],
                    'language' => 'en',
                    'timezone' => 'UTC',
                ],
            ]),
        ];
    }
}
