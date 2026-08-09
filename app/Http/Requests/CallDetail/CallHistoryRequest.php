<?php

namespace App\Http\Requests\CallDetail;

use App\Enums\CallStatus;
use App\Models\CallDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the call-history search and pagination query string.
 *
 * Its own request class because the parameters arrive on the Lead detail
 * page, shared with that page's own query string — the prefixed names keep
 * the two from colliding.
 */
class CallHistoryRequest extends FormRequest
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
            'call_q' => ['nullable', 'string', 'max:100'],
            'call_status' => ['nullable', new Enum(CallStatus::class)],
            'call_sort' => ['nullable', Rule::in(CallDetail::SORTABLE)],
            'call_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Normalised filters for CallDetailService::paginateForLead().
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'q' => $this->string('call_q')->trim()->value() ?: null,
            'call_status' => $this->input('call_status') ?: null,
            'sort' => $this->input('call_sort'),
            'direction' => $this->input('call_direction'),
        ];
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->input('call_q')) || filled($this->input('call_status'));
    }
}
