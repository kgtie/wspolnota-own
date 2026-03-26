<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Waliduje dane wejściowe dla endpointu auth API v1.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'min:3', 'max:190'],
            'password' => ['required', 'string'],
            'device' => ['required', 'array'],
            'device.platform' => ['required', 'string', 'in:ios,android'],
            'device.device_id' => ['required', 'string', 'min:8', 'max:128'],
            'device.device_name' => ['nullable', 'string', 'max:120'],
            'device.app_version' => ['required', 'string', 'max:30'],
        ];
    }
}
