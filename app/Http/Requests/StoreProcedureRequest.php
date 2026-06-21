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
                'unique:procedures,name,NULL,id,procedure_type_id,' . $this->procedure_type_id,
            ],
            'procedure_type_id' => ['required', 'exists:practice_types,id'],
            'default_notes' => ['nullable', 'string'],
            'deadline_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
