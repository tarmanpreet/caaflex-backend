<?php

namespace App\Http\Requests;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPracticeDeadlinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAnyDeadline', Practice::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    PracticeDeadline::STATUS_PENDING,
                    PracticeDeadline::STATUS_IN_PROGRESS,
                    PracticeDeadline::STATUS_COMPLETED,
                    PracticeDeadline::STATUS_CANCELLED,
                ]),
            ],
            'priority' => ['nullable', 'integer', 'min:1', 'max:4'],
            'timing' => ['nullable', Rule::in(['open', 'overdue', 'upcoming'])],
            'sort' => ['nullable', Rule::in(['title', 'deadline_at', 'status', 'priority'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
