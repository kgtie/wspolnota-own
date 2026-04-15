<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Waliduje dane wejściowe dla endpointu auth API v1.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).+$/'],
            'default_parish_id' => [
                'nullable',
                'integer',
                Rule::exists('parishes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'device' => ['nullable', 'array'],
            'device.platform' => ['required_with:device', 'string', 'in:ios,android'],
            'device.device_id' => ['required_with:device', 'string', 'min:8', 'max:128'],
            'device.device_name' => ['nullable', 'string', 'max:120'],
            'device.app_version' => ['required_with:device', 'string', 'max:30'],
        ];
    }
}
