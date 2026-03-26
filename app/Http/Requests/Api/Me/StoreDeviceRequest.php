<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Waliduje dane wejściowe dla endpointu profilu użytkownika w API v1.
 */
class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:fcm'],
            'platform' => ['required', 'string', 'in:android,ios'],
            'push_token' => ['required', 'string', 'max:4096'],
            'device_id' => ['required', 'string', 'min:8', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['required', 'string', 'max:30'],
            'locale' => ['nullable', 'string', 'max:16'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'permission_status' => ['nullable', 'string', 'in:authorized,provisional,denied,not_determined'],
            'parish_id' => ['nullable', 'integer', 'exists:parishes,id'],
        ];
    }
}
