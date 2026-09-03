<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\User;
use App\Rules\AssignableUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'assigned_user_id' => ['nullable', new AssignableUser],
            'notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $appointment = $this->route('appointment');
                $assignedUserId = $this->has('assigned_user_id')
                    ? $this->integer('assigned_user_id')
                    : (int) $appointment?->assigned_user_id;

                if ($assignedUserId === 0) {
                    return;
                }

                $branchId = $this->has('branch_id')
                    ? ($this->filled('branch_id') ? $this->integer('branch_id') : null)
                    : $appointment?->branch_id;
                $assignedUser = User::query()->find($assignedUserId);

                if ($assignedUser !== null && ! $assignedUser->canAccessBranchId($branchId)) {
                    $validator->errors()->add('assigned_user_id', 'L’utente assegnato non può accedere alla filiale dell’appuntamento.');
                }
            },
        ];
    }
}
