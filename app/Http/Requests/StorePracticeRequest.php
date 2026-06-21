<?php

namespace App\Http\Requests;

use App\Models\Procedure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePracticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Practice::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'client_profile_id' => ['required', 'exists:client_profiles,id'],
            'type' => ['required', 'in:730,ISEE,IMU_TASI,RED_INPS,SUCCESSIONE,BONUS_AGEVOLAZIONI,ALTRO'],
            'status' => ['nullable', 'in:nuova,in_lavorazione,in_attesa_documenti,completata,annullata,sospesa'],
            'reference_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'notes' => ['nullable', 'string'],
            'procedure_id' => ['nullable', 'exists:procedures,id'],
            'practice_type_id' => ['nullable', 'exists:practice_types,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'deadline_at' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Include auto-derived practice_type_id in validated data
        if ($key === null) {
            $validated['practice_type_id'] = $this->input('practice_type_id');
        }

        return $validated;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $procedureId = $this->procedure_id;
            $practiceTypeId = $this->practice_type_id;

            if ($procedureId) {
                $procedure = Procedure::find($procedureId);

                if (! $procedure) {
                    return; // Already validated by 'exists' rule
                }

                if (! $practiceTypeId) {
                    // Auto-derive practice_type_id from procedure
                    $this->merge(['practice_type_id' => $procedure->procedure_type_id]);
                } elseif ($practiceTypeId != $procedure->procedure_type_id) {
                    // Validate match if both provided
                    $validator->errors()->add(
                        'practice_type_id',
                        'The practice_type_id does not match the procedure\'s type.'
                    );
                }
            }
        });
    }
}
