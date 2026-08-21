<?php

namespace App\Http\Requests;

use App\Models\Practice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeDocumentExpirationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Practice $practice */
        $practice = $this->route('practice');

        return $this->user()->can('uploadDocument', $practice);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expires_on' => ['present', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
