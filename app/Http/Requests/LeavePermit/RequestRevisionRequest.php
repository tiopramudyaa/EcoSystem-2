<?php

namespace App\Http\Requests\LeavePermit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HR sends a Leave / Permit application back to the employee for changes. The notes tell the employee what to fix.
 * Who may do this is decided by the route (`menu.can:hr_general.leave_permit.tab-inbox,edit`), not here.
 */
class RequestRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'revision_notes' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'revision_notes.required' => 'Please tell the employee what needs to change.',
            'revision_notes.max'      => 'The notes may not be longer than 1000 characters.',
        ];
    }
}
