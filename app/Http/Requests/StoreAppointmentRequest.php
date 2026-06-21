<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Appointment::class);
    }

    public function rules(): array
    {
        return [
            'client_profile_id' => ['required', 'exists:client_profiles,id'],
            'practice_type_id' => ['required', 'exists:practice_types,id'],
            'practice_id' => [
                'nullable',
                Rule::exists('practices', 'id')->where(function ($query) {
                    return $query
                        ->where('client_profile_id', $this->integer('client_profile_id'))
                        ->where('practice_type_id', $this->integer('practice_type_id'));
                }),
            ],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
