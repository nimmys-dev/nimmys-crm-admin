<?php

namespace App\Http\Requests\Staff;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateStaffRequest extends StoreStaffRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Ignore the record being edited, otherwise saving without changing
        // the address fails its own unique rule.
        $rules['email'] = [
            'required', 'email:rfc', 'max:255',
            Rule::unique('users', 'email')->ignore($this->route('staff')),
        ];

        // Leaving the password blank keeps the current one.
        $rules['password'] = ['nullable', 'confirmed', Password::defaults()];

        return $rules;
    }

    /**
     * Guards against an admin locking themselves out of the panel.
     *
     * Both rules only bite when editing your own record, so an admin can
     * still demote or deactivate anyone else.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $staff = $this->route('staff');

            if (! $staff || $staff->id !== $this->user()->id) {
                return;
            }

            if ($this->input('role') !== UserRole::Admin->value) {
                $validator->errors()->add(
                    'role',
                    'You cannot change your own role away from Admin. Ask another Admin to do it.'
                );
            }

            if ($this->input('status') !== UserStatus::Active->value) {
                $validator->errors()->add(
                    'status',
                    'You cannot deactivate your own account.'
                );
            }
        });
    }
}
