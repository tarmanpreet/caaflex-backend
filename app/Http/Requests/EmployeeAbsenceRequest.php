<?php

namespace App\Http\Requests;

use App\Models\EmployeeAbsence;
use Illuminate\Validation\Rule;

class EmployeeAbsenceRequest extends AttendanceRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PATCH')) {
            return ['certificate_number' => ['nullable', 'string', 'max:100'], 'note' => ['nullable', 'string', 'max:2000']];
        }

        return [
            'type' => ['required', Rule::in(array_keys(EmployeeAbsence::TYPES))],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.now('Europe/Rome')->addYears(2)->toDateString()],
            'all_day' => ['required', 'boolean'],
            'time_from' => ['nullable', 'required_if:all_day,false,0', 'date_format:H:i'],
            'time_to' => ['nullable', 'required_if:all_day,false,0', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
            'certificate_number' => ['nullable', 'string', 'max:100', 'prohibited_unless:type,malattia'],
        ];
    }
}
