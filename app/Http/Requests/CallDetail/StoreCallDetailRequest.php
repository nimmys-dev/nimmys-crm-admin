<?php

namespace App\Http\Requests\CallDetail;

use App\Enums\CallStatus;
use App\Enums\UserRole;
use App\Models\CallDetail;
use App\Models\User;
use App\Support\HtmlSanitiser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreCallDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [CallDetail::class, $this->route('lead')]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $status = $this->input('call_status');
        $isAnswered = $status === CallStatus::Answered->value;
        $isNotAnswered = $status === CallStatus::NotAnswered->value;
        $hasInterest = $this->has('interest') && $this->input('interest') !== null;
        $isInterested = $this->boolean('interest');
        $isItemSold = $this->boolean('is_item_sold');

        return [
            'call_status' => ['required', new Enum(CallStatus::class)],

            // Mandatory across all decision branches
            'remarks' => ['required', 'string', 'max:5000'],

            'called_by' => ['nullable'],
            'called_date' => ['nullable', 'date', 'before_or_equal:today'],
            'called_time' => ['nullable', 'date_format:H:i'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:14400'],

            // Not Answered branch OR (Answered + Interested + Not Sold)
            'next_followup_date' => [
                'nullable',
                // Rule::requiredIf($isNotAnswered || ($isAnswered && $isInterested && ! $isItemSold)),
                'date',
                // 'after_or_equal:called_date',
            ],

            // Answered branch
            'interest' => [
                'nullable',
                Rule::requiredIf($isAnswered),
                'boolean',
            ],

            // Answered + Not Interested
            'reason' => [
                'nullable',
                Rule::requiredIf($isAnswered && $hasInterest && ! $isInterested),
                'string',
                'max:1000',
            ],

            // Answered + Interested
            'is_item_sold' => [
                'nullable',
                Rule::requiredIf($isAnswered && $hasInterest && $isInterested),
                'boolean',
            ],

            // Answered + Interested + Item Sold
            'invoice_number' => [
                'nullable',
                Rule::requiredIf($isAnswered && $isInterested && $isItemSold),
                'string',
                'max:100',
            ],

            // 'invoice_file' => [
            //     'nullable',
            //     Rule::requiredIf($this->isInvoiceFileRequired()),
            //     'file',
            //     'mimes:pdf,jpg,jpeg,png,webp',
            //     'max:10240',
            // ],
        ];
    }

    protected function isInvoiceFileRequired(): bool
    {
        $status = $this->input('call_status');
        $isAnswered = $status === CallStatus::Answered->value;
        $isInterested = $this->boolean('interest');
        $isItemSold = $this->boolean('is_item_sold');

        return $isAnswered && $isInterested && $isItemSold;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $status = $this->input('call_status');

            if (! $status) {
                return;
            }

            $case = CallStatus::tryFrom($status);

            // A call that never connected has no meaningful duration.
            if ($case === CallStatus::NotAnswered && (int) $this->input('duration') > 0) {
                $validator->errors()->add(
                    'duration',
                    "A 'Not Answered' call has no duration to record."
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'called_date.before_or_equal' => 'The call date cannot be in the future.',
            'called_time.date_format' => 'Enter the call time as HH:MM.',
            // 'next_followup_date.after_or_equal' => 'The follow-up must be on or after the call date.',
            'next_followup_date.required' => 'Next follow-up date is required.',
            'interest.required' => 'Please indicate whether the customer is interested.',
            'reason.required' => 'Please provide a reason for not interested.',
            'is_item_sold.required' => 'Please specify whether the item was sold.',
            'invoice_number.required' => 'Invoice number is required when an item is sold.',
            // 'invoice_file.required' => 'Invoice file upload is required when an item is sold.',
            'remarks.required' => 'Remarks are mandatory.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'call_status' => 'call status',
            'called_by' => 'called by',
            'called_date' => 'call date',
            'called_time' => 'call time',
            'next_followup_date' => 'next follow-up date',
            'interest' => 'interest',
            'reason' => 'reason',
            'is_item_sold' => 'is item sold',
            'invoice_number' => 'invoice number',
            'invoice_file' => 'invoice file',
            'remarks' => 'remarks',
        ];
    }

    protected function prepareForValidation(): void
    {
        $interest = $this->input('interest');
        $isItemSold = $this->input('is_item_sold');

        $merge = [
            'remarks' => HtmlSanitiser::clean($this->input('remarks')),
            'called_date' => $this->input('called_date') ?: today()->toDateString(),
            'called_time' => $this->input('called_time') ?: now()->format('H:i'),
        ];

        if ($interest !== null && $interest !== '') {
            $merge['interest'] = filter_var($interest, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($isItemSold !== null && $isItemSold !== '') {
            $merge['is_item_sold'] = filter_var($isItemSold, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($merge);
    }

    /**
     * Validated attributes, with called_by defaulting to the logged-in user
     * and automatic current date/time.
     *
     * @return array<string, mixed>
     */
    public function callAttributes(): array
    {
        $data = $this->safe()->except(['invoice_file']);

        // Always use the logged in user
        $data['called_by'] = $this->user()->id;
        $data['called_date'] = $data['called_date'] ?? today()->toDateString();
        $data['called_time'] = $data['called_time'] ?? now()->format('H:i');
        $data['duration'] = null;

        // Clean up fields based on branch
        $status = $data['call_status'] ?? null;
        if ($status === CallStatus::NotAnswered->value) {
            $data['interest'] = null;
            $data['reason'] = null;
            $data['is_item_sold'] = null;
            $data['invoice_number'] = null;
        } elseif ($status === CallStatus::Answered->value) {
            if (empty($data['interest'])) {
                $data['is_item_sold'] = null;
                $data['invoice_number'] = null;
                $data['next_followup_date'] = null;
            } else {
                $data['reason'] = null;
                if (! empty($data['is_item_sold'])) {
                    $data['next_followup_date'] = null;
                }
            }
        }

        return $data;
    }

    /**
     * Users a call may be attributed to.
     *
     * @return array<int, string>
     */
    public static function callerOptions(): array
    {
        return User::query()
            ->active()
            ->where(function ($query) {
                $query->whereIn('role', [UserRole::Admin, UserRole::Manager])
                    ->orWhere(fn ($q) => $q->where('role', UserRole::Employee)->where('lead_module_access', true));
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
