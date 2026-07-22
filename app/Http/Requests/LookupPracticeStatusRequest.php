<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LookupPracticeStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:10', 'regex:/^[A-Z0-9]{10}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Inserisci il codice della pratica.',
            'code.size' => 'Il codice deve contenere esattamente 10 caratteri.',
            'code.regex' => 'Usa soltanto lettere e numeri.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = preg_replace('/[\s-]+/', '', (string) $this->input('code'));

        $this->merge([
            'code' => Str::upper($code ?? ''),
        ]);
    }
}
