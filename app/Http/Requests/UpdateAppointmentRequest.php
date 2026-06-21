<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appointment'));
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:'.implode(',', Appointment::STATUSES)],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
