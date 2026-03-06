<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc',
                'max:190',
                Rule::unique('users', 'email')->ignore($this->user()?->getKey()),
            ],
            'current_password' => ['required', 'string'],
        ];
    }
}
