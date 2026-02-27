<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
