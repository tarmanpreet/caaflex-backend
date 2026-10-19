<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindAvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['client_profile_id' => ['required', 'integer', 'exists:client_profiles,id'], 'practice_type_id' => ['required', 'integer', 'exists:practice_types,id'], 'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(90)->toDateString()], 'appointment_id' => ['nullable', 'integer', 'exists:appointments,id']];
    }
}
