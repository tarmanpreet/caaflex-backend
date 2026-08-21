<?php

namespace App\Http\Requests;

use App\Models\PracticeDeadline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePracticeDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api') ?? $this->user();

        return $user && Gate::allows('updateDeadline', $this->route('practice'));
    }

    public function rules(): array
    {
        $practiceId = $this->route('practice')->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'deadline_at' => ['sometimes', 'required', 'date'],
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    PracticeDeadline::STATUS_PENDING,
                    PracticeDeadline::STATUS_IN_PROGRESS,
                    PracticeDeadline::STATUS_COMPLETED,
                    PracticeDeadline::STATUS_CANCELLED,
                ]),
            ],
            'priority' => ['sometimes', 'required', 'integer', 'min:1', 'max:4'],
            'user_id' => [
                'sometimes',
                'nullable',
                'exists:users,id',
                Rule::exists('practice_user', 'user_id')->where('practice_id', $practiceId),
            ],
        ];
    }
}
