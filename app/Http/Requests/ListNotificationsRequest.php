<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNotificationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section' => ['nullable', 'string', Rule::in(array_keys(config('notifications.sections')))],
            'status' => ['nullable', 'string', Rule::in(['read', 'unread'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function unreadFilter(): ?bool
    {
        return match ($this->validated('status')) {
            'unread' => true,
            'read' => false,
            default => null,
        };
    }
}
