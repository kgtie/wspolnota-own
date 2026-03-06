<?php

namespace App\Http\Requests\Api\Office;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
