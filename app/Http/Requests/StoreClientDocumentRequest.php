<?php

namespace App\Http\Requests;

use App\Models\ClientProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClientProfile $client */
        $client = $this->route('client');

        return $this->user()->can('uploadDocument', $client);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'files'           => 'required|array|min:1',
            'files.*'         => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'descriptions'    => 'nullable|array',
            'descriptions.*'  => 'nullable|string|max:255',
        ];
    }
}
