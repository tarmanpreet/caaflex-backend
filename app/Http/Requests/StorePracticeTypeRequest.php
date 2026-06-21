<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePracticeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PracticeType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:practice_types,name'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
