<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $managedUser = $this->route('user');
        $allowedRoles = collect($this->user()->assignableRoles())
            ->push($managedUser?->roles()->value('name'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser?->id)],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'practice_type_ids' => ['nullable', 'array'],
            'practice_type_ids.*' => ['integer', 'exists:practice_types,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($this->user()->accessibleBranchIds()->all())],
        ];
    }
}
