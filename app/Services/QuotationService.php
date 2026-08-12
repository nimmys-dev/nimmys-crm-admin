<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use App\Support\QuotationReference;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for lead quotations.
 *
 * One per lead: create() is only ever called once for a given lead (the
 * controller checks lead->quotation first), and update() always operates on
 * that same row. Both funnel through calculateTotals() and syncItems() so
 * the money on the record can never drift from its line items.
 */
class QuotationService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{description: string, quantity: mixed, rate: mixed}>  $items
     */
    public function create(Lead $lead, array $attributes, array $items, User $actor): Quotation
    {
        return QuotationReference::withNext(function (string $reference) use ($lead, $attributes, $items, $actor) {
            $quotation = $lead->quotation()->create([
                ...$attributes,
                ...$this->calculateTotals($items, $attributes['discount_percent'] ?? null, $attributes['tax_percent'] ?? null),
                'reference' => $reference,
                'created_by' => $actor->id,
            ]);

            $this->syncItems($quotation, $items);

            return $quotation;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{description: string, quantity: mixed, rate: mixed}>  $items
     */
    public function update(Quotation $quotation, array $attributes, array $items): Quotation
    {
        return DB::transaction(function () use ($quotation, $attributes, $items) {
            $quotation->update([
                ...$attributes,
                ...$this->calculateTotals($items, $attributes['discount_percent'] ?? null, $attributes['tax_percent'] ?? null),
            ]);

            $this->syncItems($quotation, $items);

            return $quotation->refresh();
        });
    }

    public function delete(Quotation $quotation): void
    {
        $quotation->delete();
    }

    /**
     * Replace every line item wholesale.
     *
     * Simpler and safer than diffing against the submitted rows: the form
     * lets items be added, removed and reordered freely, so there is no
     * stable identity to match existing rows against. sort_order is the
     * array position, preserving the order they were entered in.
     *
     * @param  array<int, array{description: string, quantity: mixed, rate: mixed}>  $items
     */
    private function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $quotation->items()->create([
                'description' => $item['description'],
                'quantity' => $this->decimal((float) $item['quantity']),
                'rate' => $this->decimal((float) $item['rate']),
                'amount' => $this->decimal($this->lineAmount($item)),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * subtotal, discount_percent, tax_percent and total for a set of items —
     * the one place a percentage turns into money, so the stored total can
     * never disagree with the lines that make it up.
     *
     * @param  array<int, array{description: string, quantity: mixed, rate: mixed}>  $items
     * @return array<string, mixed>
     */
    private function calculateTotals(array $items, mixed $discountPercent, mixed $taxPercent): array
    {
        $discountPercent = filled($discountPercent) ? (float) $discountPercent : null;
        $taxPercent = filled($taxPercent) ? (float) $taxPercent : null;

        $subtotal = round(collect($items)->sum(fn (array $item) => $this->lineAmount($item)), 2);
        $discountAmount = $discountPercent ? round($subtotal * $discountPercent / 100, 2) : 0.0;
        $taxAmount = $taxPercent ? round(($subtotal - $discountAmount) * $taxPercent / 100, 2) : 0.0;

        return [
            'subtotal' => $this->decimal($subtotal),
            'discount_percent' => $discountPercent !== null ? $this->decimal($discountPercent) : null,
            'tax_percent' => $taxPercent !== null ? $this->decimal($taxPercent) : null,
            'total' => $this->decimal($subtotal - $discountAmount + $taxAmount),
        ];
    }

    /**
     * @param  array{description: string, quantity: mixed, rate: mixed}  $item
     */
    private function lineAmount(array $item): float
    {
        return round((float) $item['quantity'] * (float) $item['rate'], 2);
    }

    /**
     * Format a float as a fixed-point numeric string.
     *
     * The model casts these columns 'decimal:2', which passes the raw value
     * to brick/math — a native PHP float there is deprecated (float
     * imprecision has no place in money) and logs a warning on every access.
     * A plain "123.40" string sidesteps it entirely.
     */
    private function decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
