<?php

namespace App\Http\Requests;

use App\Rules\IsDirectorRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'director_id' => ['nullable', 'uuid', 'exists:users,id', new IsDirectorRole()],
            'is_active' => ['boolean'],
        ];
    }
}
