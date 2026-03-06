<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'min:2', 'max:80'],
            'last_name' => ['sometimes', 'required', 'string', 'min:2', 'max:80'],
            'default_parish_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('parishes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }
}
