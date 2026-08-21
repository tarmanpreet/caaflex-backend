<?php

namespace App\Http\Requests;

use App\Models\ClientProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientDocumentExpirationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ClientProfile $client */
        $client = $this->route('client');

        return $this->user()->can('uploadDocument', $client);
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
