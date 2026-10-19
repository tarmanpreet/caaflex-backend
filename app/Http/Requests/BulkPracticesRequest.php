<?php

namespace App\Http\Requests;

use App\Models\Practice;
use App\Rules\AssignableUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkPracticesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('practices.update') || $this->user()->can('practices.assign');
    }

    public function rules(): array
    {
        return ['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['required', 'integer', 'distinct', 'exists:practices,id'], 'action' => ['required', Rule::in(['status', 'assign'])], 'status' => ['required_if:action,status', 'nullable', Rule::in(Practice::STATUSES)], 'user_ids' => ['required_if:action,assign', 'array', 'max:20'], 'user_ids.*' => ['integer', 'distinct', new AssignableUser]];
    }
}
