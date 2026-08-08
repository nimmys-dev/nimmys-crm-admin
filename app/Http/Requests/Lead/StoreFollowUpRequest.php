<?php

namespace App\Http\Requests\Lead;

use App\Enums\FollowUpType;
use App\Support\HtmlSanitiser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addFollowUp', $this->route('lead')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(FollowUpType::class)],
            'notes' => ['nullable', 'string', 'max:20000'],

            // Scheduling is forward-looking; logging is backward-looking.
            'scheduled_at' => ['nullable', 'date'],
            'log_now' => ['nullable', 'boolean'],
            'outcome' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isLogged = $this->boolean('log_now');
            $scheduled = $this->input('scheduled_at');

            // A row that is neither scheduled nor logged records nothing.
            if (! $isLogged && blank($scheduled)) {
                $validator->errors()->add(
                    'scheduled_at',
                    'Choose a date for the next follow-up, or mark this as already done.'
                );
            }

            // A future date on something already completed is contradictory.
            if ($isLogged && filled($scheduled) && now()->lt($scheduled)) {
                $validator->errors()->add(
                    'scheduled_at',
                    'A completed follow-up cannot be dated in the future.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => HtmlSanitiser::clean($this->input('notes')),
            'log_now' => $this->boolean('log_now'),
        ]);
    }

    /**
     * Attributes for the follow-up row.
     *
     * `log_now` is a UI concept, not a column — it decides whether the row is
     * born complete.
     *
     * @return array<string, mixed>
     */
    public function followUpAttributes(): array
    {
        $data = $this->safe()->except('log_now');

        $data['completed_at'] = $this->boolean('log_now') ? now() : null;

        // A logged follow-up with no explicit date happened now.
        if ($this->boolean('log_now') && blank($data['scheduled_at'] ?? null)) {
            $data['scheduled_at'] = now();
        }

        return $data;
    }
}
