<?php

namespace App\Http\Requests\Staff;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    /**
     * Route middleware already enforces `can:staff.manage`; this is the
     * second gate, so the rule holds even if the route is remounted.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('staff.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // Uniqueness spans soft-deleted rows because the column keeps its
            // UNIQUE index — a trashed member still owns their address.
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],

            'role' => ['required', new Enum(UserRole::class)],

            'shop_id' => ['nullable', Rule::exists('shops', 'id')->whereNull('deleted_at')],

            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alternate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/', 'different:phone'],

            'joining_date' => ['nullable', 'date', 'before_or_equal:today'],

            'salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],

            // Increments are always scheduled forward; a past date would fire
            // the reminder window immediately and never clear.
            'increment_date' => ['nullable', 'date', 'after_or_equal:today'],
            'increment_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            // Checkboxes are absent from the payload when unticked, so these
            // must tolerate a missing key rather than requiring one.
            'increment_notification' => ['nullable', 'boolean'],
            'lead_module_access' => ['nullable', 'boolean'],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100'],

            'password' => ['required', 'confirmed', Password::defaults()],

            'status' => ['required', new Enum(UserStatus::class)],

            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'That email is already registered. It may belong to a deleted staff member.',
            'phone.regex' => 'The mobile number may contain only digits, spaces and + - ( ).',
            'alternate_phone.regex' => 'The alternate mobile may contain only digits, spaces and + - ( ).',
            'alternate_phone.different' => 'The alternate mobile must differ from the primary mobile.',
            'photo.dimensions' => 'The photo must be at least 100×100 pixels.',
            'photo.max' => 'The photo may not be larger than 2 MB.',
            'joining_date.before_or_equal' => 'The joining date cannot be in the future.',
            'increment_date.after_or_equal' => 'The increment date must be today or later.',
        ];
    }

    /**
     * Whether the current user may set the privileged toggles.
     */
    public function canManageSettings(): bool
    {
        return $this->user()?->can('staff.settings.manage') ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shop_id' => 'shop',
            'phone' => 'mobile',
            'alternate_phone' => 'alternate mobile',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => filled($this->input('email')) ? strtolower(trim($this->input('email'))) : null,

            // An unticked checkbox is simply absent from the payload, so
            // normalise both toggles to a real boolean before validation.
            'increment_notification' => $this->boolean('increment_notification'),
            'lead_module_access' => $this->boolean('lead_module_access'),
        ]);
    }

    /**
     * Validated attributes with the privileged toggles stripped for anyone
     * who may not set them.
     *
     * Enforced server-side rather than only hiding the inputs: a non-Admin
     * could otherwise post the fields directly. Falls back to the model
     * default on create and the stored value on update.
     *
     * @return array<string, mixed>
     */
    public function staffAttributes(): array
    {
        $data = $this->safe()->except(['photo', 'password', 'password_confirmation']);

        if (! $this->canManageSettings()) {
            unset($data['increment_notification'], $data['lead_module_access']);
        }

        return $data;
    }
}
