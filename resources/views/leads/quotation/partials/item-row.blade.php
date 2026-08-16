{{--
    One quotation line: item/product, quantity, rate, plus a computed
    (read-only, JS-filled) amount and a remove button.

    $index is either a real array key (server-rendered rows, so a failed
    validation's error messages — keyed "items.<index>.<field>" — land on
    the right row) or the literal string "__INDEX__" for the JS clone
    template; see the script block below.
--}}
@props(['index', 'item' => []])

{{--
    Laid out with .quotation-item-* classes from crm.css rather than the
    theme's Tailwind grid: that build is purged down to the demo pages'
    classes and only ships col-span-6 and col-span-12, so col-span-4 (and
    every md: variant but md:col-span-6) silently does nothing — which is
    what squeezed these fields into a single 1/12 column. Plain CSS cannot
    be purged out from under them.

    step="any" on the two numeric inputs: the spinner arrows move by whole
    units, while a typed decimal (2.5 qty, 150.50 rate) still validates —
    a fixed step="1" would reject those as "not a valid value".
--}}
<div class="quotation-item-row" data-quotation-row>
    <x-form.input
        :name="'items['.$index.'][description]'"
        label="Item / product"
        :value="$item['description'] ?? ''"
        placeholder="e.g. Alpha ILCE 7RM5 B Sony"
        required
        col="quotation-item-desc"
    />

    <x-form.input
        :name="'items['.$index.'][quantity]'"
        label="Qty"
        type="number"
        :value="$item['quantity'] ?? '1'"
        min="0.01" step="any" inputmode="decimal"
        required
        class="quotation-item-input"
        data-quotation-qty="1"
        col="quotation-item-field-sm"
    />

    <x-form.input
        :name="'items['.$index.'][rate]'"
        label="Rate (incl. tax)"
        type="number"
        :value="$item['rate'] ?? ''"
        min="0" step="any" inputmode="decimal"
        required
        class="quotation-item-input"
        data-quotation-rate="1"
        col="quotation-item-field-md"
    />

    <x-form.input
        :name="'items['.$index.'][tax_percent]'"
        label="Tax %"
        type="number"
        :value="$item['tax_percent'] ?? '18.00'"
        min="0" max="100" step="any" inputmode="decimal"
        class="quotation-item-input"
        data-quotation-tax-percent="1"
        col="quotation-item-field-sm"
    />

    <div class="quotation-item-field-md">
        <label class="form-label">Basic Rate</label>
        <input
            type="text" class="form-control quotation-item-input bg-gray-50 dark:bg-gray-800"
            value="{{ $item['basic_rate'] ?? '0.00' }}"
            readonly tabindex="-1" data-quotation-basic-rate
        />
    </div>

    <div class="quotation-item-field-md">
        <label class="form-label">Tax Amt</label>
        <input
            type="text" class="form-control quotation-item-input bg-gray-50 dark:bg-gray-800"
            value="{{ $item['tax_amount'] ?? '0.00' }}"
            readonly tabindex="-1" data-quotation-tax-amount
        />
    </div>

    <div class="quotation-item-field-md">
        <label class="form-label">Total</label>
        <input
            type="text" class="form-control quotation-item-input bg-gray-50 dark:bg-gray-800 font-semibold"
            value="{{ $item['amount'] ?? '0.00' }}"
            readonly tabindex="-1" data-quotation-amount
        />
    </div>

    <div class="quotation-item-remove">
        <button
            type="button" class="btn quotation-remove-btn" data-quotation-remove-row
            aria-label="Remove item"
        >
            <i class="ti ti-trash" aria-hidden="true"></i>
        </button>
    </div>
</div>
