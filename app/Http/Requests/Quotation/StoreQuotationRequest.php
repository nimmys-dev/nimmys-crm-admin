<?php

namespace App\Http\Requests\Quotation;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating or replacing the one quotation a lead may have.
 *
 * Authorised the same way as editing the lead itself — preparing a
 * quotation is part of working it, same as closing it or logging a call.
 */
class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lead')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],

            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],

            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'terms' => ['nullable', 'string', 'max:5000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'customer name',
            'customer_address' => 'customer address',
            'issue_date' => 'date',
            'valid_until' => 'valid until',
            'discount_percent' => 'discount',
            'tax_percent' => 'tax',
            'items.*.description' => 'item',
            'items.*.quantity' => 'quantity',
            'items.*.rate' => 'rate',
        ];
    }

    /**
     * The quotation's own columns, with items split out — those are handled
     * separately by QuotationService, which replaces them wholesale rather
     * than mass-assigning them onto the quotation row.
     *
     * @return array<string, mixed>
     */
    public function quotationAttributes(): array
    {
        return $this->safe()->except('items');
    }

    /**
     * @return array<int, array{description: string, quantity: mixed, rate: mixed}>
     */
    public function items(): array
    {
        return $this->safe()->only('items')['items'] ?? [];
    }

    public function lead(): Lead
    {
        return $this->route('lead');
    }
}
