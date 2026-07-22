<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->branch);
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $forbiddenParentIds = $branch instanceof Branch
            ? Branch::descendantIdsFor([$branch->id])->all()
            : [];

        return [
            'parent_id' => [
                Rule::requiredIf($branch instanceof Branch && $branch->parent_id !== null),
                'nullable',
                'integer',
                Rule::in($this->user()->accessibleBranchIds()->all()),
                Rule::notIn($forbiddenParentIds),
            ],
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
