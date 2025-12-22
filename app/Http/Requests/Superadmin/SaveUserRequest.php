<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            // Hasło wymagane tylko przy tworzeniu, przy edycji opcjonalne (zmiana)
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            
            'role' => ['required', 'integer', 'in:0,1,2'],
            'home_parish_id' => ['nullable', 'exists:parishes,id'],
            
            // Pola sterujące "władczym" zatwierdzaniem
            'is_email_verified' => ['nullable', 'boolean'],
            'is_parish_verified' => ['nullable', 'boolean'],
            
            'avatar_remove' => ['nullable', 'boolean'],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
        ];
    }
}