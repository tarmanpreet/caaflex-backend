<?php

namespace App\Http\Requests;

use App\Models\ClientDelegation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClientDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\FamilyAccessService::class)->manage($this->user(), $this->route('client'));
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'relationship' => ['nullable', 'string', 'max:80'], 'scopes' => ['required', 'array', 'min:1', 'max:5'], 'scopes.*' => ['required', 'string', 'distinct', Rule::in(ClientDelegation::SCOPES)], 'expires_on' => ['nullable', 'date_format:Y-m-d', 'after:today', 'before_or_equal:'.now()->addYears(3)->toDateString()]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $scopes = $this->input('scopes', []);
            if (! is_array($scopes)) {
                return;
            }
            if (! in_array('view', $scopes, true)) {
                $validator->errors()->add('scopes', 'La consultazione della pratica è necessaria.');
            }
            if (in_array('upload', $scopes, true) && ! in_array('documents', $scopes, true)) {
                $validator->errors()->add('scopes', 'Per caricare documenti occorre anche il permesso Documenti.');
            }
        }];
    }
}
