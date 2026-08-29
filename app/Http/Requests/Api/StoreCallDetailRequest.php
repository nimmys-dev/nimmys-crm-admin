<?php

namespace App\Http\Requests\Api;

use App\Enums\CallStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCallDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $status = $this->input('call_status');

        $isAnswered = $status === CallStatus::Answered->value;
        $isNotAnswered = $status === CallStatus::NotAnswered->value;

        $hasInterest = $this->has('interest')
            && $this->input('interest') !== null
            && $this->input('interest') !== '';

        $isInterested = $this->boolean('interest');
        $isItemSold = $this->boolean('is_item_sold');

        return [

            /*
            |--------------------------------------------------------------------------
            | Call Status
            |--------------------------------------------------------------------------
            */
            'call_status' => [
                'required',
                Rule::enum(CallStatus::class),
            ],

            /*
            |--------------------------------------------------------------------------
            | Date / Time
            |--------------------------------------------------------------------------
            */
            'called_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'called_time' => [
                'nullable',
                'date_format:H:i',
            ],

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */
            'remarks' => [
                'required',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Interest
            | Required when call is Answered
            |--------------------------------------------------------------------------
            */
            'interest' => [
                'nullable',
                'boolean',
                Rule::requiredIf($isAnswered),
            ],

            /*
            |--------------------------------------------------------------------------
            | Reason
            | Required when Answered + Not Interested
            |--------------------------------------------------------------------------
            */
            'reason' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(
                    $isAnswered
                    && $hasInterest
                    && ! $isInterested
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Sold
            | Required when Answered + Interested
            |--------------------------------------------------------------------------
            */
            'is_item_sold' => [
                'nullable',
                'boolean',
                Rule::requiredIf(
                    $isAnswered
                    && $hasInterest
                    && $isInterested
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Invoice Number
            | Required when Answered + Interested + Item Sold
            |--------------------------------------------------------------------------
            */
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    $isAnswered
                    && $isInterested
                    && $isItemSold
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Next Follow-up
            | Required when:
            | 1. Not Answered
            | 2. Answered + Interested + Not Sold
            |--------------------------------------------------------------------------
            */
            'next_followup_date' => [
                'nullable',
                'date',
                'after_or_equal:called_date',
                Rule::requiredIf(
                    $isNotAnswered
                    || (
                        $isAnswered
                        && $isInterested
                        && ! $isItemSold
                    )
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Invoice File
            | Required when Answered + Interested + Item Sold
            |--------------------------------------------------------------------------
            */
            // 'invoice_file' => [
            //     'nullable',
            //     'file',
            //     'mimes:jpg,jpeg,png,pdf,webp',
            //     'max:10240',
            //     Rule::requiredIf(
            //         $isAnswered
            //         && $isInterested
            //         && $isItemSold
            //     ),
            // ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Convert Form-Data 0/1 values to boolean
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        $merge = [
            'called_date' => $this->input('called_date')
                ?: today()->toDateString(),

            'called_time' => $this->input('called_time')
                ?: now()->format('H:i'),
        ];

        if (
            $this->input('interest') !== null
            && $this->input('interest') !== ''
        ) {
            $merge['interest'] = filter_var(
                $this->input('interest'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
        }

        if (
            $this->input('is_item_sold') !== null
            && $this->input('is_item_sold') !== ''
        ) {
            $merge['is_item_sold'] = filter_var(
                $this->input('is_item_sold'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
        }

        $this->merge($merge);
    }

    /*
    |--------------------------------------------------------------------------
    | Additional Validation
    |--------------------------------------------------------------------------
    */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $status = $this->input('call_status');

            if (! $status) {
                return;
            }

            $case = CallStatus::tryFrom($status);

            /*
             * Not Answered call cannot have duration.
             */
            if (
                $case === CallStatus::NotAnswered
                && (int) $this->input('duration') > 0
            ) {
                $validator->errors()->add(
                    'duration',
                    "A 'Not Answered' call has no duration to record."
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [

            'call_status.required' =>
                'Call status is required.',

            'called_date.before_or_equal' =>
                'Call date cannot be in the future.',

            'called_time.date_format' =>
                'Call time must be in HH:MM format.',

            'remarks.required' =>
                'Remarks are required.',

            'interest.required' =>
                'Please specify whether the customer is interested.',

            'reason.required' =>
                'Reason is required when the customer is not interested.',

            'is_item_sold.required' =>
                'Please specify whether the item was sold.',

            'invoice_number.required' =>
                'Invoice number is required when the item is sold.',

            'next_followup_date.required' =>
                'Next follow-up date is required.',

            'next_followup_date.after_or_equal' =>
                'The follow-up date must be on or after the call date.',

            // 'invoice_file.required' =>
            //     'Invoice file is required when the item is sold.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Call Attributes
    |--------------------------------------------------------------------------
    */
    public function callAttributes(): array
    {
        $data = $this->safe()->except([
            'invoice_file',
        ]);

        // Always use logged-in user
        $data['called_by'] = $this->user()->id;

        // Default date/time
        $data['called_date'] =
            $data['called_date']
            ?? today()->toDateString();

        $data['called_time'] =
            $data['called_time']
            ?? now()->format('H:i');

        // Duration
        $data['duration'] = null;

        $status = $data['call_status'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | NOT ANSWERED
        |--------------------------------------------------------------------------
        */
        if ($status === CallStatus::NotAnswered->value) {

            $data['interest'] = null;
            $data['reason'] = null;
            $data['is_item_sold'] = null;
            $data['invoice_number'] = null;

        }

        /*
        |--------------------------------------------------------------------------
        | ANSWERED
        |--------------------------------------------------------------------------
        */
        elseif ($status === CallStatus::Answered->value) {

            /*
             * Not Interested
             */
            if (empty($data['interest'])) {

                $data['is_item_sold'] = null;
                $data['invoice_number'] = null;
                $data['next_followup_date'] = null;

            }

            /*
             * Interested
             */
            else {

                $data['reason'] = null;

                /*
                 * Item Sold
                 */
                if (! empty($data['is_item_sold'])) {
                    $data['next_followup_date'] = null;
                }
            }
        }

        return $data;
    }
}