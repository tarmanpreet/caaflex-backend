<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Procedure::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:procedures,name,NULL,id,procedure_type_id,'.$this->procedure_type_id,
            ],
            'procedure_type_id' => ['required', 'exists:practice_types,id'],
            'default_notes' => ['nullable', 'string'],
            'deadline_days' => ['nullable', 'integer', 'min:0'],
            'deadline_templates' => ['nullable', 'array', 'max:50'],
            'deadline_templates.*.title' => ['required', 'string', 'max:255'],
            'deadline_templates.*.notes' => ['nullable', 'string'],
            'deadline_templates.*.offset_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'deadline_templates.*.offset_hours' => ['required', 'integer', 'min:0', 'max:23'],
            'deadline_templates.*.priority' => ['required', 'integer', 'min:1', 'max:4'],
        ];
    }
}
