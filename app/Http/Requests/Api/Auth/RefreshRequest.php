<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Waliduje dane wejściowe dla endpointu auth API v1.
 */
class RefreshRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:40'],
            'device' => ['nullable', 'array'],
            'device.device_id' => ['nullable', 'string', 'min:8', 'max:128'],
        ];
    }
}
