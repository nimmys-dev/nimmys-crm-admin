{{--
    Shared by create and edit. $items is the server default (one blank row,
    or the existing quotation's rows); old('items', $items) means a failed
    submission redisplays exactly what was typed, keyed the same way so
    per-row validation errors land on the right row.
--}}
@php
    $rows = old('items', $items);
@endphp

<x-card title="Customer">
    <div class="grid grid-cols-12 gap-4">
        <x-form.input
            name="customer_name" label="Customer name"
            :value="$quotation->customer_name" required
        />

        <x-form.input
            name="issue_date" label="Date" type="date"
            :value="optional($quotation->issue_date)->format('Y-m-d')" required
        />

        <x-form.textarea
            name="customer_address" label="Customer address"
            :value="$quotation->customer_address" rows="2" col="col-span-12"
        />

        <x-form.input
            name="valid_until" label="Valid until" type="date"
            :value="optional($quotation->valid_until)->format('Y-m-d')"
            hint="Shown on the PDF. Leave blank if it does not expire."
        />
    </div>
</x-card>

<x-card title="Items">
    <div id="quotation-items" data-quotation-items data-next-index="{{ count($rows) }}">
        @foreach ($rows as $index => $item)
            @include('leads.quotation.partials.item-row', ['index' => $index, 'item' => $item])
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm mt-3" data-quotation-add-row>
        <i class="ti ti-plus" aria-hidden="true"></i> Add item
    </button>

    @error('items')
        <p class="invalid-feedback d-block mt-2">{{ $message }}</p>
    @enderror

    <hr class="my-4" />

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6 md:col-start-7">
            <dl class="quotation-totals">
                <div>
                    <dt>Total Basic Value</dt>
                    <dd data-quotation-basic-total>0.00</dd>
                </div>
                <div>
                    <dt>Total Tax</dt>
                    <dd data-quotation-tax-total>0.00</dd>
                </div>
                <div class="quotation-totals-grand">
                    <dt>Grand Total</dt>
                    <dd data-quotation-total>0.00</dd>
                </div>
            </dl>
        </div>
    </div>
</x-card>

<x-card title="Terms & Conditions">
    <x-form.textarea
        name="terms" label="Terms & Conditions" :value="$quotation->terms" rows="6"
        hint="Printed at the bottom left of the PDF — delivery, tax, payment terms, etc."
        col="col-span-12"
    />
</x-card>

{{-- The JS "Add item" clone source. Rendered through the same partial as
     the real rows, with index="__INDEX__", so the two can never drift. --}}
<template id="quotation-item-template">
    @include('leads.quotation.partials.item-row', ['index' => '__INDEX__', 'item' => ['quantity' => '1', 'tax_percent' => '18.00']])
</template>

@push('scripts')
    <script>
        (function () {
            var container = document.getElementById('quotation-items');
            var template = document.getElementById('quotation-item-template');
            var addButton = document.querySelector('[data-quotation-add-row]');

            if (!container || !template) return;

            /*
             * Server-truth calculation happens in QuotationService; this is
             * a live preview only, so a stray keystroke can never disagree
             * with what gets saved for longer than a render frame.
             */
            function toNumber(value) {
                var n = parseFloat(value);
                return isNaN(n) ? 0 : n;
            }

            function money(n) {
                return n.toFixed(2);
            }

            function recalculate() {
                var totalBasic = 0;
                var totalTax = 0;
                var grandTotal = 0;

                container.querySelectorAll('[data-quotation-row]').forEach(function (row) {
                    var qty = toNumber(row.querySelector('[data-quotation-qty]').value);
                    var rate = toNumber(row.querySelector('[data-quotation-rate]').value);
                    var taxPercentInput = row.querySelector('[data-quotation-tax-percent]');
                    var taxPercent = taxPercentInput && taxPercentInput.value !== '' ? toNumber(taxPercentInput.value) : 18.0;

                    var basicRate = taxPercent > 0 ? (rate / (1 + (taxPercent / 100))) : rate;
                    var taxAmt = (rate - basicRate) * qty;
                    var amount = qty * rate;

                    var basicInput = row.querySelector('[data-quotation-basic-rate]');
                    if (basicInput) basicInput.value = money(basicRate);

                    var taxAmtInput = row.querySelector('[data-quotation-tax-amount]');
                    if (taxAmtInput) taxAmtInput.value = money(taxAmt);

                    var amountInput = row.querySelector('[data-quotation-amount]');
                    if (amountInput) amountInput.value = money(amount);

                    totalBasic += (basicRate * qty);
                    totalTax += taxAmt;
                    grandTotal += amount;
                });

                setText('[data-quotation-basic-total]', money(totalBasic));
                setText('[data-quotation-tax-total]', money(totalTax));
                setText('[data-quotation-total]', money(grandTotal));

                updateRemoveButtons();
            }

            function setText(selector, value) {
                var el = document.querySelector(selector);
                if (el) el.textContent = value;
            }

            // At least one row must remain — the server also enforces this,
            // but disabling the last row's remove button avoids a round trip
            // just to be told so.
            function updateRemoveButtons() {
                var rows = container.querySelectorAll('[data-quotation-row]');
                rows.forEach(function (row) {
                    var button = row.querySelector('[data-quotation-remove-row]');
                    if (button) button.disabled = rows.length <= 1;
                });
            }

            function addRow() {
                var nextIndex = parseInt(container.getAttribute('data-next-index'), 10) || 0;
                var html = template.innerHTML.split('__INDEX__').join(String(nextIndex));

                var wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();

                container.appendChild(wrapper.firstElementChild);
                container.setAttribute('data-next-index', String(nextIndex + 1));

                recalculate();
            }

            addButton && addButton.addEventListener('click', addRow);

            container.addEventListener('click', function (event) {
                var button = event.target.closest('[data-quotation-remove-row]');
                if (!button || button.disabled) return;

                var row = button.closest('[data-quotation-row]');
                if (row) row.remove();

                recalculate();
            });

            // Delegated so newly added rows are covered without rebinding.
            container.addEventListener('input', function (event) {
                if (event.target.matches('[data-quotation-qty], [data-quotation-rate], [data-quotation-tax-percent]')) {
                    recalculate();
                }
            });

            recalculate();
        })();
    </script>
@endpush
