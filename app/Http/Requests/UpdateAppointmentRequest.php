<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => [
                'sometimes',
                'date',
                'after:now'
            ],
            'duration' => [
                'sometimes',
                'integer',
                'min:15',
                'max:480' // 8 hours max
            ],
            'location' => [
                'nullable',
                'string',
                'max:255'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'status' => [
                'sometimes',
                Rule::in(['scheduled', 'confirmed', 'cancelled', 'completed', 'no_show'])
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Appointment cannot be rescheduled to a past date.',
            'duration.min' => 'Appointment duration must be at least 15 minutes.',
            'duration.max' => 'Appointment duration cannot exceed 8 hours.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
