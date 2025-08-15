<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
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
        // Handle nested clientInfo object
        if ($this->has('clientInfo')) {
            $clientInfo = $this->input('clientInfo');
            $this->merge([
                'client_first_name' => $clientInfo['firstName'] ?? $this->client_first_name,
                'client_last_name' => $clientInfo['lastName'] ?? $this->client_last_name,
                'client_email' => $clientInfo['email'] ?? $this->client_email,
                'client_phone' => $clientInfo['phone'] ?? $this->client_phone,
                'client_company' => $clientInfo['company'] ?? $this->client_company,
            ]);
        } else {
            // Handle direct camelCase properties
            $this->merge([
                'client_first_name' => $this->clientFirstName ?? $this->client_first_name,
                'client_last_name' => $this->clientLastName ?? $this->client_last_name,
                'client_email' => $this->clientEmail ?? $this->client_email,
                'client_phone' => $this->clientPhone ?? $this->client_phone,
                'client_company' => $this->clientCompany ?? $this->client_company,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_first_name' => ['required', 'string', 'max:255'],
            'client_last_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:20'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
