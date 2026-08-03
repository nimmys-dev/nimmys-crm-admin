<?php

namespace App\Http\Requests\Staff;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the filter query string.
 *
 * Filters from a URL are user input like any other. `sort` in particular is
 * checked against a whitelist before it can reach an ORDER BY.
 */
class StaffIndexRequest extends FormRequest
{
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
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', new Enum(UserRole::class)],
            'status' => ['nullable', new Enum(UserStatus::class)],
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')],
            'sort' => ['nullable', Rule::in(User::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }

    /**
     * @return array{q: ?string, role: ?string, status: ?string, shop_id: ?string, sort: string, direction: string, per_page: int}
     */
    public function filters(): array
    {
        return [
            'q' => $this->string('q')->trim()->value() ?: null,
            'role' => $this->input('role') ?: null,
            'status' => $this->input('status') ?: null,
            'shop_id' => $this->input('shop_id') ?: null,
            'sort' => $this->input('sort', 'created_at'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->input('q'))
            || filled($this->input('role'))
            || filled($this->input('status'))
            || filled($this->input('shop_id'));
    }
}
