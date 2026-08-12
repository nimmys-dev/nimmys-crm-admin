<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Closing a lead as Won or Lost from the detail page.
 *
 * Deliberately narrower than UpdateLeadRequest: only the outcome and a
 * reason are asked for here, so this can be a small form on the show page
 * instead of sending the visitor through the full edit form. A reason is
 * required for either outcome — Won and Lost are both decisions worth a
 * one-line record of why.
 */
class CloseLeadRequest extends FormRequest
{
    /**
     * Same permission as editing the lead — closing is part of working it —
     * plus a check that it is not already closed, so a stale form cannot
     * reopen or re-close a decided lead.
     */
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead
            && ($this->user()?->can('update', $lead) ?? false)
            && $lead->status->isOpen();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(LeadStatus::closed(), 'value'))],
            'lost_reason' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lost_reason.required' => 'Please record a reason for closing this lead.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'outcome',
            'lost_reason' => 'reason',
        ];
    }
}
