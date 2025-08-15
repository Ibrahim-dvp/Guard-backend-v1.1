<?php

namespace App\Http\Requests;

use App\Rules\AllowedToCreateRole;
use App\Rules\UserCanAccessOrganization;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by the controller middleware
    }

    /**
     * Prepare the data for validation by converting camelCase to snake_case.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->firstName ?? $this->first_name,
            'last_name' => $this->lastName ?? $this->last_name,
            'organization_id' => $this->organizationId ?? $this->organization_id,
            'role_name' => $this->roleName ?? $this->role_name,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id', new UserCanAccessOrganization()],
            'role_name' => ['required', 'string', 'exists:roles,name', new AllowedToCreateRole()],
        ];
    }
}
