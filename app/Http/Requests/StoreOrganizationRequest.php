<?php

namespace App\Http\Requests;

use App\Rules\IsDirectorRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation by converting camelCase to snake_case.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->parentId ?? $this->parent_id,
            'director_id' => $this->directorId ?? $this->director_id,
            'is_active' => $this->isActive ?? $this->is_active,
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
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'director_id' => ['nullable', 'uuid', 'exists:users,id', new IsDirectorRole()],
            'is_active' => ['boolean'],
        ];
    }
}
