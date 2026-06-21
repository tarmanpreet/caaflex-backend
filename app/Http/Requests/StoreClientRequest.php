<?php

namespace App\Http\Requests;

use App\Models\ClientProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientProfile::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'date_of_birth'  => 'required|date|before:today',
            'fiscal_code'    => 'nullable|string|size:16|unique:client_profiles,fiscal_code',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'province'       => 'nullable|string|size:2',
            'postal_code'    => 'nullable|string|size:5',
            'notes'          => 'nullable|string|max:1000',
            'create_account' => 'boolean',
            'account_email'  => 'required_if:create_account,true|nullable|email|unique:users,email',
        ];
    }
}
