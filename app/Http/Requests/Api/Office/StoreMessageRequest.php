<?php

namespace App\Http\Requests\Api\Office;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Waliduje dane wejściowe dla endpointu kancelarii online w API v1.
 */
class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasFile('files') && blank($this->input('body'))) {
                $validator->errors()->add('body', 'Treść wiadomości lub załącznik są wymagane.');
            }
        });
    }
}
