<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line item on a quotation — an item/product, quantity and rate.
 *
 * amount is stored, not computed on read; see the migration for why.
 */
class QuotationItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quotation_id',
        'description',
        'quantity',
        'rate',
        'tax_percent',
        'basic_rate',
        'tax_amount',
        'amount',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'basic_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Quotation, QuotationItem> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
