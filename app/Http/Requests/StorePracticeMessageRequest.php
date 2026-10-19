<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePracticeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(\App\Services\PracticeAccess::class);

        return $access->message($this->user(), $this->route('practice')) && (! $this->hasFile('file') || $access->upload($this->user(), $this->route('practice')));
    }

    public function rules(): array
    {
        return ['body' => ['nullable', 'required_without:file', 'string', 'max:5000'], 'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:20480'], 'visibility' => ['required', \Illuminate\Validation\Rule::in(['public', 'internal'])]];
    }
}
