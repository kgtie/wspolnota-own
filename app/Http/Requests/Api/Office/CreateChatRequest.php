<?php

namespace App\Http\Requests\Api\Office;

use Illuminate\Foundation\Http\FormRequest;

class CreateChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parish_id' => ['required', 'integer', 'exists:parishes,id'],
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
