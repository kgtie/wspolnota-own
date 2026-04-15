<?php

namespace App\Http\Requests\Api\Office;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Waliduje dane wejściowe dla endpointu kancelarii online w API v1.
 */
class CreateChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parish_id' => [
                'required',
                'integer',
                Rule::exists('parishes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
