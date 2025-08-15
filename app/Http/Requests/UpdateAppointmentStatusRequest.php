<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
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
            'status' => [
                'required', 
                'string',
                Rule::in(['scheduled', 'confirmed', 'cancelled', 'completed', 'no_show'])
            ],
            'reason' => ['sometimes', 'string', 'max:500'], // Optional reason for status change
            'notes' => ['sometimes', 'string'], // Optional additional notes
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The appointment status is required.',
            'status.in' => 'The appointment status must be one of: scheduled, confirmed, cancelled, completed, no_show.',
        ];
    }
}
