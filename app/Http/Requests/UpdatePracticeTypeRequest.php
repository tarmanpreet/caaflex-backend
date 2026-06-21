<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('practice_type'));
    }

    public function rules(): array
    {
        $id = $this->route('practice_type')->id ?? '';

        return [
            'name' => ['required', 'string', 'max:50', "unique:practice_types,name,{$id}"],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
