<?php

namespace App\Http\Requests\Lead;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('lead')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Null unassigns. Anyone named must actually be able to work the
            // Lead module, or the lead would vanish into an account that
            // cannot open it.
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereIn('role', [UserRole::Admin->value, UserRole::Manager->value])
                                ->orWhere(function ($q) {
                                    $q->where('role', UserRole::Employee->value)
                                        ->where('lead_module_access', true);
                                });
                        });
                }),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'That user cannot be given leads. They must be active and have Lead module access.',
        ];
    }

    /**
     * Users a lead may be assigned to.
     *
     * @return array<int, string>
     */
    public static function assignableOptions(): array
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
