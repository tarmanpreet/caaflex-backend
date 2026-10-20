<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ReviewEmployeeAbsenceRequest extends AttendanceRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000']];
    }
}
