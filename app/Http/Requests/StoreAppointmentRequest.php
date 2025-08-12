<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
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
            'lead_id' => [
                'required',
                'uuid',
                'exists:leads,id'
            ],
            'scheduled_at' => [
                'required',
                'date',
                'after:now'
            ],
            'duration' => [
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
                'nullable',
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
            'lead_id.required' => 'Please select a lead for this appointment.',
            'lead_id.exists' => 'The selected lead does not exist.',
            'scheduled_at.required' => 'Please specify when the appointment is scheduled.',
            'scheduled_at.after' => 'Appointment cannot be scheduled in the past.',
            'duration.min' => 'Appointment duration must be at least 15 minutes.',
            'duration.max' => 'Appointment duration cannot exceed 8 hours.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default duration if not provided
        if (!$this->has('duration')) {
            $this->merge(['duration' => 60]); // 1 hour default
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'scheduled']);
        }
    }
}
