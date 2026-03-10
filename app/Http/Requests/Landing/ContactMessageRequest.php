<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'parish' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'imię i nazwisko',
            'email' => 'adres e-mail',
            'parish' => 'parafia',
            'phone' => 'telefon',
            'subject' => 'temat',
            'message' => 'wiadomość',
        ];
    }
}
