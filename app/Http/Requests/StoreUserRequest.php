<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn (Builder $query) => $query->where('guard_name', 'web')),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'practice_type_ids' => ['nullable', 'array'],
            'practice_type_ids.*' => ['integer', 'exists:practice_types,id'],
        ];
    }
}