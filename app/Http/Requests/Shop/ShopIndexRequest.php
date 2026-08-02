<?php

namespace App\Http\Requests\Shop;

use App\Enums\ShopStatus;
use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the filter query string.
 *
 * Filters arriving from a URL are user input like any other. Validating them
 * here means the controller can trust them, and `sort` in particular is
 * checked against a whitelist before it reaches an ORDER BY.
 */
class ShopIndexRequest extends FormRequest
{
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
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', new Enum(ShopStatus::class)],
            'city' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(Shop::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }

    /**
     * Normalised filters with defaults applied.
     *
     * @return array{q: ?string, status: ?string, city: ?string, sort: string, direction: string, per_page: int}
     */
    public function filters(): array
    {
        return [
            'q' => $this->string('q')->trim()->value() ?: null,
            'status' => $this->input('status') ?: null,
            'city' => $this->input('city') ?: null,
            'sort' => $this->input('sort', 'name'),
            'direction' => $this->input('direction', 'asc'),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }

    /**
     * Whether any filter is active, so the view can offer a reset control.
     */
    public function hasActiveFilters(): bool
    {
        return filled($this->input('q'))
            || filled($this->input('status'))
            || filled($this->input('city'));
    }
}
