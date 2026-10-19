<?php

namespace App\Http\Requests;

use App\Models\Practice;
use Illuminate\Foundation\Http\FormRequest;

class RenewPracticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Practice::class) && app(\App\Services\PracticeAccess::class)->staff($this->user(), $this->route('practice'));
    }

    public function rules(): array
    {
        return ['reference_year' => ['required', 'integer', 'min:'.max(now()->year, ($this->route('practice')->reference_year ?? now()->year) + 1), 'max:'.(now()->year + 5)]];
    }
}
