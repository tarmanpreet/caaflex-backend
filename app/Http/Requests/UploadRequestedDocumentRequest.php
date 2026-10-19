<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRequestedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\PracticeAccess::class)->upload($this->user(), $this->route('practice'));
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:20480']];
    }
}
