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
        return [
            'call_status' => ['required', new Enum(CallStatus::class)],

            'remarks' => ['nullable', 'string', 'max:5000'],

            // Only users who manage leads may attribute a call to someone
            // else; stripped for everyone else in callAttributes().
            'called_by' => [
                'nullable',
                Rule::exists('users', 'id')->whereNull('deleted_at')->where('status', 'active'),
            ],

            // A call cannot have happened tomorrow.
            'called_date' => ['required', 'date', 'before_or_equal:today'],
            'called_time' => ['required', 'date_format:H:i'],

            // A follow-up is by definition ahead of the call.
            'next_followup_date' => ['nullable', 'date', 'after_or_equal:called_date'],

            // Seconds. 4 hours is a generous ceiling that still catches a
            // mistyped value.
            'duration' => ['nullable', 'integer', 'min:0', 'max:14400'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $status = $this->input('call_status');

            if (! $status) {
                return;
            }

            $case = CallStatus::tryFrom($status);

            // Scheduling a follow-up on a closed outcome is contradictory —
            // there is nothing left to follow up.
            if ($case?->isTerminal() && filled($this->input('next_followup_date'))) {
                $validator->errors()->add(
                    'next_followup_date',
                    "A follow-up cannot be scheduled on a '{$case->label()}' call."
                );
            }

            // A call that never connected has no meaningful duration.
            if ($case && ! $case->reachedContact() && (int) $this->input('duration') > 0) {
                $validator->errors()->add(
                    'duration',
                    "A '{$case->label()}' call has no duration to record."
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
            'next_followup_date.after_or_equal' => 'The follow-up must be on or after the call date.',
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Remarks are rendered in the timeline, so they go through the
            // same sanitiser the Lead description uses.
            'remarks' => HtmlSanitiser::clean($this->input('remarks')),
        ]);
    }

    /**
     * Validated attributes, with attribution stripped for users who may not
     * log a call on someone else's behalf.
     *
     * Enforced server-side, not just by hiding the field.
     *
     * @return array<string, mixed>
     */
    public function callAttributes(): array
    {
        $data = $this->safe()->all();

        if (! $this->user()->can('leads.manage')) {
            unset($data['called_by']);
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
