<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the filter query string.
 *
 * `sort` reaches an ORDER BY, so it is checked against Lead::SORTABLE before
 * the repository ever sees it.
 */
class LeadIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Lead::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', new Enum(LeadStatus::class)],
            'priority' => ['nullable', new Enum(LeadPriority::class)],
            'source' => ['nullable', new Enum(LeadSource::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')],
            'due' => ['nullable', Rule::in(['overdue'])],
            'sort' => ['nullable', Rule::in(Lead::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'q' => $this->string('q')->trim()->value() ?: null,
            'status' => $this->input('status') ?: null,
            'priority' => $this->input('priority') ?: null,
            'source' => $this->input('source') ?: null,
            'assigned_to' => $this->input('assigned_to') ?: null,
            'shop_id' => $this->input('shop_id') ?: null,
            'due' => $this->input('due') ?: null,
            'sort' => $this->input('sort', 'created_at'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }

    public function hasActiveFilters(): bool
    {
        return collect(['q', 'status', 'priority', 'source', 'assigned_to', 'shop_id', 'due'])
            ->contains(fn (string $key) => filled($this->input($key)));
    }
}
