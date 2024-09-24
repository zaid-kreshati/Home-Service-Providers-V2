<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return $this->user()->hasRole('client');
    }

    public function rules()
    {
        return [
            'date' => 'required|date|after:now',
            'hours' => 'required|date_format:H:i',
            'description' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'date.after' => 'The appointment date must be a future date.',
            'hours.date_format' => 'The hours must be in the correct format (H:i).',
        ];
    }
}
