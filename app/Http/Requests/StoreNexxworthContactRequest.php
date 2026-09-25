<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNexxworthContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config('branding.customer_code') === 'nexxworth';
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'privacy_accepted' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }
}
