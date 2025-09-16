<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isPatient();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'insurance_provider' => ['sometimes', 'nullable', 'string', 'max:255'],
            'insurance_member_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'emergency_contact' => ['sometimes', 'nullable', 'array'],
            'emergency_contact.name' => ['required_with:emergency_contact', 'string', 'max:255'],
            'emergency_contact.phone' => ['required_with:emergency_contact', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'dob.before' => 'Date of birth must be in the past.',
            'gender.in' => 'Please select a valid gender option.',
            'emergency_contact.name.required_with' => 'Emergency contact name is required when providing emergency contact.',
            'emergency_contact.phone.required_with' => 'Emergency contact phone is required when providing emergency contact.',
        ];
    }
}