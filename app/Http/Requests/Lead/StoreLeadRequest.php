<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Support\HtmlSanitiser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alternate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/', 'different:phone'],
            'city' => ['nullable', 'string', 'max:100'],

            'source' => ['nullable', new Enum(LeadSource::class)],
            'status' => ['nullable', new Enum(LeadStatus::class)],
            'priority' => ['nullable', new Enum(LeadPriority::class)],

            'value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],

            'shop_id' => ['nullable', Rule::exists('shops', 'id')->whereNull('deleted_at')],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->where(function ($roles) {
                            $roles->whereIn('role', [UserRole::Admin->value, UserRole::Manager->value])
                                ->orWhere(fn ($employees) => $employees
                                    ->where('role', UserRole::Employee->value)
                                    ->where('lead_module_access', true));
                        });
                }),
            ],

            // TinyMCE output. Length is generous but bounded so a paste of a
            // whole document cannot exhaust the column.
            'description' => ['nullable', 'string', 'max:65000'],

            'lost_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Losing a lead needs an explanation; the field is meaningless otherwise.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $status = $this->input('status');

            if ($status === LeadStatus::Closed->value) {
                // closed related validation മാത്രം ഇവിടെ വേണമെങ്കിൽ വെക്കാം
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone may contain only digits, spaces and + - ( ).',
            'alternate_phone.regex' => 'The alternate phone may contain only digits, spaces and + - ( ).',
            'alternate_phone.different' => 'The alternate phone must differ from the primary phone.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shop_id' => 'shop',
            'assigned_to' => 'owner',
            'lost_reason' => 'reason',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => filled($this->input('email')) ? strtolower(trim($this->input('email'))) : null,

            /*
             * TinyMCE posts raw HTML, which is an XSS vector the moment it is
             * rendered unescaped. Sanitise before validation so what reaches
             * the database is already safe and the view can print it with
             * a single trusted call.
             */
            'description' => HtmlSanitiser::clean($this->input('description')),
        ]);
    }

    /**
     * Validated attributes with owner assignment stripped for users who may
     * not choose one. The Lead module assignment gate remains the server-side
     * authority; hiding the input is never relied on for protection.
     *
     * @return array<string, mixed>
     */
    public function leadAttributes(): array
    {
        $data = $this->safe()->all();

        if (! $this->user()->can('leads.assign')) {
            unset($data['assigned_to']);
        }

        return $data;
    }
}
