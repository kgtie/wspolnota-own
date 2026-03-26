<?php

namespace App\Http\Requests\Api\ParishApprovals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Waliduje dane wejściowe dla endpointu zatwierdzania parafian w API v1.
 */
class PendingParishApprovalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parish_id' => [
                'required',
                'integer',
                Rule::exists('parishes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }
}
