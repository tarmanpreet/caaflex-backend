<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('procedure'));
    }

    public function rules(): array
    {
        $procedureId = $this->route('procedure')->id ?? '';

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                "unique:procedures,name,{$procedureId},id,procedure_type_id,{$this->procedure_type_id}",
            ],
            'procedure_type_id' => ['required', 'exists:practice_types,id'],
            'default_notes' => ['nullable', 'string'],
            'deadline_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
