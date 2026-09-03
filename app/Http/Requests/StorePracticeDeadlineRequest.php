<?php

namespace App\Http\Requests;

use App\Models\PracticeDeadline;
use App\Rules\AssignableUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePracticeDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api') ?? $this->user();

        return $user && Gate::allows('createDeadline', $this->route('practice'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'deadline_at' => ['required', 'date'],
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
            'user_id' => [
                'nullable',
                new AssignableUser,
            ],
        ];
    }
}
