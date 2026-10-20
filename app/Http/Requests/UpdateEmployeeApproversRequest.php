<?php

namespace App\Http\Requests;

class UpdateEmployeeApproversRequest extends AttendanceRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(\App\Services\EmployeeAttendanceService::class)->isManager($this->user());
    }

    public function rules(): array
    {
        return ['approver_ids' => ['present', 'array', 'max:50'], 'approver_ids.*' => ['integer', 'distinct', 'exists:users,id']];
    }
}
