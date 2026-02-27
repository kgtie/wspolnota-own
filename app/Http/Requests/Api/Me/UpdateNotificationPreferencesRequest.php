<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'news' => ['required', 'array'],
            'news.push' => ['required', 'boolean'],
            'news.email' => ['required', 'boolean'],
            'announcements' => ['required', 'array'],
            'announcements.push' => ['required', 'boolean'],
            'announcements.email' => ['required', 'boolean'],
            'office_messages' => ['required', 'array'],
            'office_messages.push' => ['required', 'boolean'],
            'office_messages.email' => ['required', 'boolean'],
            'parish_approval_status' => ['required', 'array'],
            'parish_approval_status.push' => ['required', 'boolean'],
            'parish_approval_status.email' => ['required', 'boolean'],
            'auth_security' => ['required', 'array'],
            'auth_security.push' => ['required', 'boolean'],
            'auth_security.email' => ['required', 'boolean'],
        ];
    }
}
