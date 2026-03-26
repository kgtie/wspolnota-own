<?php

namespace App\Http\Requests\Api\Me;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Waliduje dane wejściowe dla endpointu profilu użytkownika w API v1.
 */
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
            'mass_reminders' => ['sometimes', 'array'],
            'mass_reminders.push' => ['required_with:mass_reminders', 'boolean'],
            'mass_reminders.email' => ['required_with:mass_reminders', 'boolean'],
            'office_messages' => ['required', 'array'],
            'office_messages.push' => ['required', 'boolean'],
            'office_messages.email' => ['required', 'boolean'],
            'parish_approval_status' => ['required', 'array'],
            'parish_approval_status.push' => ['required', 'boolean'],
            'parish_approval_status.email' => ['required', 'boolean'],
            'auth_security' => ['required', 'array'],
            'auth_security.push' => ['required', 'boolean'],
            'auth_security.email' => ['required', 'boolean'],
            'manual_messages' => ['sometimes', 'array'],
            'manual_messages.push' => ['required_with:manual_messages', 'boolean'],
            'manual_messages.email' => ['required_with:manual_messages', 'boolean'],
        ];
    }
}
