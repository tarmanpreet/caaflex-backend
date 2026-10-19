<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePracticeDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\PracticeAccess::class)->manage($this->user(), $this->route('practice'));
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'instructions' => ['nullable', 'string', 'max:5000'], 'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today']];
    }
}
