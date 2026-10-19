<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequestedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\PracticeAccess::class)->manage($this->user(), $this->route('practice'));
    }

    public function rules(): array
    {
        return ['status' => ['required', \Illuminate\Validation\Rule::in(['approved', 'rejected'])], 'reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000']];
    }
}
