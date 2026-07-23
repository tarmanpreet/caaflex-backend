<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationSettingsRequest extends FormRequest
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
        $sections = array_keys(config('notifications.sections'));
        $rules = ['sections' => ['required', 'array:'.implode(',', $sections)]];

        foreach ($sections as $section) {
            $rules["sections.{$section}"] = ['required', 'array'];
            $rules["sections.{$section}.enabled"] = ['required', 'boolean'];
            $rules["sections.{$section}.mail_enabled"] = ['required', 'boolean'];
            $rules["sections.{$section}.realtime_enabled"] = ['required', 'boolean'];

            if (config("notifications.sections.{$section}.supports_reminders")) {
                $rules["sections.{$section}.reminder_offsets"] = ['present', 'array'];
                $rules["sections.{$section}.reminder_offsets.*"] = [
                    'integer',
                    Rule::in(array_map('intval', array_keys(config('notifications.reminder_options')))),
                ];
            }
        }

        return $rules;
    }
}
