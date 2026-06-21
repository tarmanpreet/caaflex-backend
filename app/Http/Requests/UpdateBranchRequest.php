<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->branch);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'size:2'],
            'postal_code' => ['required', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
