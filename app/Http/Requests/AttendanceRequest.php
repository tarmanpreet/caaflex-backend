<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->assignable()->whereKey($this->user()->id)->exists() && ! $this->user()->hasRole('cliente');
    }

    public function rules(): array
    {
        return ['month' => ['sometimes', 'date_format:Y-m'], 'user_id' => ['sometimes', 'integer', 'min:1'], 'note' => ['nullable', 'string', 'max:2000']];
    }
}
