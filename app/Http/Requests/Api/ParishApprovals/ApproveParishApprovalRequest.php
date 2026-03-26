<?php

namespace App\Http\Requests\Api\ParishApprovals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Waliduje dane wejściowe dla endpointu zatwierdzania parafian w API v1.
 */
class ApproveParishApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_code' => ['required', 'string', 'regex:/^\d{9}$/'],
            'parish_id' => [
                'required',
                'integer',
                Rule::exists('parishes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }
}
