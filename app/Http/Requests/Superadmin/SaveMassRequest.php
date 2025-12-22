<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class SaveMassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware superadmina pilnuje dostępu
    }

    public function rules(): array
    {
        return [
            'parish_id' => ['required', 'exists:parishes,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'intention' => ['required', 'string', 'min:3', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'rite' => ['required', 'string'],
            'celebrant' => ['nullable', 'string', 'max:255'],
            'stipend' => ['nullable', 'numeric', 'min:0'],
        ];
    }
    
    /**
     * Łączymy datę i czas w jeden string dla modelu
     */
    public function getStartTimestamp(): string
    {
        return $this->date . ' ' . $this->time . ':00';
    }
}