<?php

namespace App\Http\Requests;

use App\Rules\AllowedToUpdateRole;
use App\Rules\UserCanAccessOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['sometimes', 'required', 'uuid', 'exists:organizations,id', new UserCanAccessOrganization()],
            'role_name' => ['sometimes', 'string', 'exists:roles,name', new AllowedToUpdateRole()],
        ];
    }
}
