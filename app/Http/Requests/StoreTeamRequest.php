<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
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
            'organization_id' => $this->organizationId ?? $this->organization_id,
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
            'description' => ['nullable', 'string', 'max:1000'],
            'organization_id' => [
                'nullable',
                'uuid',
                Rule::exists('organizations', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                })
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                // Unique within organization scope
                Rule::unique('teams', 'slug')->where(function ($query) {
                    $organizationId = $this->input('organization_id') ?: auth()->user()->organization_id;
                    if ($organizationId) {
                        return $query->where('organization_id', $organizationId);
                    }
                    return $query;
                })
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Team name is required.',
            'name.max' => 'Team name cannot exceed 255 characters.',
            'description.max' => 'Team description cannot exceed 1000 characters.',
            'organization_id.uuid' => 'Organization ID must be a valid UUID.',
            'organization_id.exists' => 'The selected organization does not exist or is not active.',
            'slug.regex' => 'Slug must only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken within the organization.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'team name',
            'description' => 'team description',
            'organization_id' => 'organization',
            'slug' => 'team slug',
        ];
    }
}
