<?php

namespace App\Http\Requests\LeavePermit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HR rejects a Leave / Permit application. A written reason is required: the employee sees it.
 * Who may reject is decided by the route (`menu.can:hr_general.leave_permit.tab-inbox,edit`), not here.
 */
class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Please write the reason for rejecting this application.',
            'rejection_reason.max'      => 'The reason may not be longer than 1000 characters.',
        ];
    }
}
