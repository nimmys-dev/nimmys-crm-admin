<?php

namespace App\Http\Requests\Shop;

use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreShopRequest extends FormRequest
{
    /**
     * Route middleware already enforces `can:shops.manage`; this is the
     * second gate, so the rule holds even if the route is ever remounted.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('shops.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('shops', 'code')],
            'name' => ['required', 'string', 'max:255'],

            // A shop may only be managed by someone who can actually use the
            // web portal, so Employees are excluded at validation time.
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [UserRole::Admin->value, UserRole::Manager->value])
                ),
            ],

            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],

            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],

            'opened_on' => ['nullable', 'date', 'before_or_equal:today'],

            'status' => ['required', new Enum(ShopStatus::class)],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The shop code may contain only letters, numbers, hyphens and underscores.',
            'manager_id.exists' => 'The selected manager must be an Admin or Manager.',
            'opened_on.before_or_equal' => 'The opening date cannot be in the future.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'shop code',
            'manager_id' => 'manager',
            'address_line' => 'address',
            'opened_on' => 'opening date',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'email' => filled($this->input('email')) ? strtolower(trim($this->input('email'))) : null,
        ]);
    }

    /**
     * Managers eligible to run a shop.
     *
     * @return array<int, string>
     */
    public static function managerOptions(): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Manager])
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
