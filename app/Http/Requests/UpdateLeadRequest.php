<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
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
            'client_first_name' => ['sometimes', 'string', 'max:255'],
            'client_last_name' => ['sometimes', 'string', 'max:255'],
            'client_email' => ['sometimes', 'email', 'max:255'],
            'client_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'client_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'string', 'max:255'],
            'revenue' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
