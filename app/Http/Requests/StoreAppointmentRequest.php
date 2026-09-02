<?php

namespace App\Http\Requests;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $assignedUserId = $this->integer('assigned_user_id');

                if ($assignedUserId === 0) {
                    return;
                }

                $branchId = $this->filled('branch_id')
                    ? $this->integer('branch_id')
                    : ClientProfile::query()->whereKey($this->integer('client_profile_id'))->value('branch_id');
                $assignedUser = User::query()->find($assignedUserId);

                if ($assignedUser !== null && ! $assignedUser->canAccessBranchId($branchId)) {
                    $validator->errors()->add('assigned_user_id', 'L’utente assegnato non può accedere alla filiale dell’appuntamento.');
                }
            },
        ];
    }
}
