<?php

namespace App\Http\Requests;

class CorrectAttendanceRequest extends AttendanceRequest
{
    public function rules(): array
    {
        return ['user_id' => $this->route('session') ? ['prohibited'] : ['required', 'integer', 'min:1'], 'started_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ended_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:started_at'], 'reason' => ['required', 'string', 'min:5', 'max:2000']];
    }
}
