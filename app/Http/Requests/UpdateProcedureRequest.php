<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'deadline_templates' => ['nullable', 'array', 'max:50'],
            'deadline_templates.*.id' => [
                'nullable',
                'integer',
                Rule::exists('procedure_deadline_templates', 'id')
                    ->where(fn (Builder $query) => $query->where('procedure_id', $procedureId)),
            ],
            'deadline_templates.*.title' => ['required', 'string', 'max:255'],
            'deadline_templates.*.notes' => ['nullable', 'string'],
            'deadline_templates.*.offset_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'deadline_templates.*.offset_hours' => ['required', 'integer', 'min:0', 'max:23'],
            'deadline_templates.*.priority' => ['required', 'integer', 'min:1', 'max:4'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $procedure = $this->route('procedure');

                if (! $procedure instanceof \App\Models\Procedure) {
                    return;
                }

                $practiceTypeId = (int) $this->input('procedure_type_id');

                if ($practiceTypeId !== $procedure->procedure_type_id && $procedure->practices()->exists()) {
                    $validator->errors()->add(
                        'procedure_type_id',
                        'Il tipo non può essere modificato finché la procedura è collegata a delle pratiche.'
                    );
                }
            },
        ];
    }
}
