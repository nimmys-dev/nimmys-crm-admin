<?php

namespace App\Models;

use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A price quotation prepared for a lead.
 *
 * One per lead — lead_id is unique — so QuotationService always upserts
 * rather than inserting a second row. customer_name and customer_address
 * are captured at save time rather than read live from the lead, so a
 * quotation keeps showing what it actually quoted even if the lead's own
 * details change afterwards.
 *
 * Not soft-deleted, deliberately: lead_id is unique, and a soft-deleted row
 * would go on occupying that slot forever, leaving the lead unable to ever
 * get a new quotation. This is a recreatable working document, not an
 * audit trail.
 */
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'reference',
        'customer_name',
        'customer_address',
        'issue_date',
        'valid_until',
        'subtotal',
        'discount_percent',
        'tax_percent',
        'total',
        'terms',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<Lead, Quotation> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<User, Quotation> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<QuotationItem> */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    /**
     * Discount amount in currency, derived from discount_percent against the
     * stored subtotal — kept as a method rather than a column so there is
     * exactly one place the percentage is turned into money.
     */
    public function discountAmount(): float
    {
        if (! $this->discount_percent) {
            return 0.0;
        }

        return round((float) $this->subtotal * (float) $this->discount_percent / 100, 2);
    }

    public function taxAmount(): float
    {
        if (! $this->tax_percent) {
            return 0.0;
        }

        $taxable = (float) $this->subtotal - $this->discountAmount();

        return round($taxable * (float) $this->tax_percent / 100, 2);
    }

    public function amountInWords(): string
    {
        return \App\Support\NumberToWords::indianCurrency($this->total);
    }

    public function totalBasicAmount(): float
    {
        return round((float) $this->items->sum(fn ($item) => (float) $item->basic_rate * (float) $item->quantity), 2);
    }

    public function totalItemTaxAmount(): float
    {
        return round((float) $this->items->sum(fn ($item) => (float) $item->tax_amount), 2);
    }
}
