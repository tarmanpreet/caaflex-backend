<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ReviewEmployeeAbsenceRequest extends AttendanceRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['approved', 'rejected'])], 'replacement_user_id' => ['nullable', 'integer', 'exists:users,id'], 'assignments' => ['sometimes', 'array', 'max:500'], 'assignments.*.appointment_id' => ['required', 'integer', 'distinct'], 'assignments.*.user_id' => ['required', 'integer', 'exists:users,id'], 'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000']];
    }
}
