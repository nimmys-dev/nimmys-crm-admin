{{--
    Log / Edit a call against a lead.

    Attractive, interactive decision tree with segmented visual tiles,
    instant branch switching, and quick helpers.

    Expects: $lead, $call
--}}

@php
    $formId = 'call-form-' . ($call->id ?? 'new');
    $oldStatus = old('call_status', $call->call_status?->value ?? 'answered');
    $oldInterest = old('interest', $call->interest !== null ? ($call->interest ? '1' : '0') : '');
    $oldIsItemSold = old('is_item_sold', $call->is_item_sold !== null ? ($call->is_item_sold ? '1' : '0') : '');
    $currentDate = $call->called_date?->format('Y-m-d') ?? today()->format('Y-m-d');
    $currentTime = $call->called_time ? substr($call->called_time, 0, 5) : now()->format('H:i');
@endphp

<div class="call-details-form-container space-y-5" id="{{ $formId }}">
    {{-- Hidden automatic date and time --}}
    <input type="hidden" name="called_date" value="{{ old('called_date', $currentDate) }}" />
    <input type="hidden" name="called_time" value="{{ old('called_time', $currentTime) }}" />

    {{-- Hidden actual select for standard form submit --}}
    <input type="hidden" name="call_status" id="input_call_status_{{ $formId }}" value="{{ $oldStatus }}" class="call-status-select" />

    {{-- ========================================================================= --}}
    {{-- STEP 1: CALL STATUS SELECTION TILES                                       --}}
    {{-- ========================================================================= --}}
    <div>
        <label class="form-label font-semibold text-xs tracking-wider uppercase text-muted mb-2 block">
            1. Call Status <span class="text-danger-500">*</span>
        </label>

        <div class="decision-grid">
            {{-- Answered Tile --}}
            <div
                class="decision-tile status-tile {{ $oldStatus === 'answered' ? 'active-success' : '' }}"
                data-value="answered"
                onclick="setCallStatus('{{ $formId }}', 'answered')"
            >
                <div class="decision-tile-icon">
                    <i class="ti ti-phone-incoming"></i>
                </div>
                <div class="decision-tile-content">
                    <div class="decision-tile-title">Answered</div>
                    <div class="decision-tile-desc">Customer answered the phone call</div>
                </div>
                <div class="decision-tile-check">
                    <i class="ti ti-check"></i>
                </div>
            </div>

            {{-- Not Answered Tile --}}
            <div
                class="decision-tile status-tile {{ $oldStatus === 'not_answered' ? 'active-danger' : '' }}"
                data-value="not_answered"
                onclick="setCallStatus('{{ $formId }}', 'not_answered')"
            >
                <div class="decision-tile-icon">
                    <i class="ti ti-phone-off"></i>
                </div>
                <div class="decision-tile-content">
                    <div class="decision-tile-title">Not Answered</div>
                    <div class="decision-tile-desc">No answer / Busy / Switched off</div>
                </div>
                <div class="decision-tile-check">
                    <i class="ti ti-check"></i>
                </div>
            </div>
        </div>

        @error('call_status')
            <div class="form-error mt-1.5">{{ $message }}</div>
        @enderror
    </div>

    {{-- ========================================================================= --}}
    {{-- BRANCH: NOT ANSWERED                                                      --}}
    {{-- ========================================================================= --}}
    <div class="branch-not-answered branch-card space-y-3" style="display: none;">
        <div class="flex items-center gap-2 text-danger-600 font-semibold text-sm">
            <i class="ti ti-calendar-event text-lg"></i>
            <span>Schedule Follow-up for Not Answered Call</span>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-8">
                <label class="form-label">
                    Next Follow-up Date <span class="text-danger-500">*</span>
                </label>
                <div class="relative">
                    <input
                        type="date"
                        name="next_followup_date"
                        class="form-control next-followup-input"
                        min="{{ today()->toDateString() }}"
                        value="{{ old('next_followup_date', $call->next_followup_date?->format('Y-m-d')) }}"
                    />
                </div>
                <p class="form-hint text-xs text-muted mt-1">Set date for the next call attempt.</p>
                @error('next_followup_date')
                    <div class="form-error mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- BRANCH: ANSWERED                                                          --}}
    {{-- ========================================================================= --}}
    <div class="branch-answered branch-card space-y-4" style="display: none;">
        <div class="flex items-center gap-2 text-primary font-semibold text-sm border-b pb-2">
            <i class="ti ti-user-star text-lg"></i>
            <span>2. Customer Interest Outcome</span>
        </div>

        <input type="hidden" name="interest" id="input_interest_{{ $formId }}" value="{{ $oldInterest }}" class="interest-select" />

        <div class="decision-grid">
            {{-- Interested Tile --}}
            <div
                class="decision-tile interest-tile {{ $oldInterest === '1' ? 'active-success' : '' }}"
                data-value="1"
                onclick="setInterest('{{ $formId }}', '1')"
            >
                <div class="decision-tile-icon">
                    <i class="ti ti-thumb-up"></i>
                </div>
                <div class="decision-tile-content">
                    <div class="decision-tile-title">Yes, Interested</div>
                    <div class="decision-tile-desc">Customer showed positive interest</div>
                </div>
                <div class="decision-tile-check">
                    <i class="ti ti-check"></i>
                </div>
            </div>

            {{-- Not Interested Tile --}}
            <div
                class="decision-tile interest-tile {{ $oldInterest === '0' ? 'active-danger' : '' }}"
                data-value="0"
                onclick="setInterest('{{ $formId }}', '0')"
            >
                <div class="decision-tile-icon">
                    <i class="ti ti-thumb-down"></i>
                </div>
                <div class="decision-tile-content">
                    <div class="decision-tile-title">No, Not Interested</div>
                    <div class="decision-tile-desc">Customer declined or refused</div>
                </div>
                <div class="decision-tile-check">
                    <i class="ti ti-check"></i>
                </div>
            </div>
        </div>

        @error('interest')
            <div class="form-error mt-1">{{ $message }}</div>
        @enderror

        {{-- ----------------------------------------------------------------- --}}
        {{-- SUB-BRANCH: NOT INTERESTED (Reason Mandatory)                     --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="subbranch-not-interested pt-2 space-y-2" style="display: none;">
            <label class="form-label">
                Reason for Not Interested <span class="text-danger-500">*</span>
            </label>
            <input
                type="text"
                name="reason"
                id="reason_input_{{ $formId }}"
                class="form-control reason-input"
                placeholder="Select a quick reason below or type here..."
                value="{{ old('reason', $call->reason) }}"
            />

            {{-- Quick choice pills for instant completion --}}
            <div class="reason-pills-wrap">
                <span class="reason-pill" onclick="setReason('{{ $formId }}', 'Price is too high / Out of budget')">Price too high</span>
                <span class="reason-pill" onclick="setReason('{{ $formId }}', 'Purchased from competitor')">Purchased from competitor</span>
                <span class="reason-pill" onclick="setReason('{{ $formId }}', 'No current requirement')">No requirement now</span>
                <span class="reason-pill" onclick="setReason('{{ $formId }}', 'Looking for different model/spec')">Different model needed</span>
                <span class="reason-pill" onclick="setReason('{{ $formId }}', 'General enquiry only')">General enquiry only</span>
            </div>

            @error('reason')
                <div class="form-error mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- ----------------------------------------------------------------- --}}
        {{-- SUB-BRANCH: INTERESTED (Is Item Sold?)                             --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="subbranch-interested pt-2 space-y-4" style="display: none;">
            <div class="border-t pt-3">
                <label class="form-label font-semibold text-xs tracking-wider uppercase text-muted mb-2 block">
                    3. Is Item Sold? <span class="text-danger-500">*</span>
                </label>

                <input type="hidden" name="is_item_sold" id="input_item_sold_{{ $formId }}" value="{{ $oldIsItemSold }}" class="item-sold-select" />

                <div class="decision-grid">
                    {{-- Sold Yes --}}
                    <div
                        class="decision-tile item-sold-tile {{ $oldIsItemSold === '1' ? 'active-success' : '' }}"
                        data-value="1"
                        onclick="setItemSold('{{ $formId }}', '1')"
                    >
                        <div class="decision-tile-icon">
                            <i class="ti ti-shopping-bag"></i>
                        </div>
                        <div class="decision-tile-content">
                            <div class="decision-tile-title">Yes, Item Sold</div>
                            <div class="decision-tile-desc">Sale completed & invoice issued</div>
                        </div>
                        <div class="decision-tile-check">
                            <i class="ti ti-check"></i>
                        </div>
                    </div>

                    {{-- Sold No --}}
                    <div
                        class="decision-tile item-sold-tile {{ $oldIsItemSold === '0' ? 'active' : '' }}"
                        data-value="0"
                        onclick="setItemSold('{{ $formId }}', '0')"
                    >
                        <div class="decision-tile-icon">
                            <i class="ti ti-clock-pause"></i>
                        </div>
                        <div class="decision-tile-content">
                            <div class="decision-tile-title">No, Under Follow-up</div>
                            <div class="decision-tile-desc">Customer is still considering</div>
                        </div>
                        <div class="decision-tile-check">
                            <i class="ti ti-check"></i>
                        </div>
                    </div>
                </div>

                @error('is_item_sold')
                    <div class="form-error mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- ------------------------------------------------------------- --}}
            {{-- SUB-SUB-BRANCH: ITEM SOLD = YES (Invoice Number & Upload)     --}}
            {{-- ------------------------------------------------------------- --}}
            <div class="subbranch-item-sold bg-white dark:bg-dark-900 border border-success-200 dark:border-success-900/30 p-4 rounded-lg space-y-4" style="display: none;">
                <div class="flex items-center gap-2 text-success-600 font-semibold text-sm">
                    <i class="ti ti-receipt text-lg"></i>
                    <span>Invoice Details</span>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">
                            Invoice Number <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="invoice_number"
                            class="form-control invoice-number-input"
                            placeholder="e.g. INV-2026-0042"
                            value="{{ old('invoice_number', $call->invoice_number) }}"
                        />
                        @error('invoice_number')
                            <div class="form-error mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">
                            Invoice Document Upload @if(!$call->invoice_file_path)<span class="text-danger-500">*</span>@endif
                        </label>

                        <div class="call-upload-box relative" onclick="triggerCallFileUpload('{{ $formId }}')">
                            <i class="ti ti-cloud-upload text-2xl text-primary mb-1 block"></i>
                            <span class="text-xs font-semibold text-primary upload-label-{{ $formId }}">Click to upload Invoice Document</span>
                            <p class="text-xs text-muted mt-0.5">PDF, JPG, PNG up to 10MB</p>
                            <input
                                type="file"
                                name="invoice_file"
                                id="invoice_file_input_{{ $formId }}"
                                class="hidden invoice-file-input"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                onchange="handleCallFileSelect('{{ $formId }}', this)"
                            />
                        </div>

                        @if ($call->invoice_file_path)
                            <div class="mt-2 text-xs flex items-center gap-1.5 text-primary">
                                <i class="ti ti-file-check text-sm"></i>
                                <a href="{{ $call->invoiceUrl() }}" target="_blank" class="hover:underline font-medium">
                                    View existing uploaded invoice
                                </a>
                            </div>
                        @endif

                        @error('invoice_file')
                            <div class="form-error mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------- --}}
            {{-- SUB-SUB-BRANCH: ITEM SOLD = NO (Next Follow-up Date)         --}}
            {{-- ------------------------------------------------------------- --}}
            <div class="subbranch-item-not-sold bg-white dark:bg-dark-900 border p-4 rounded-lg space-y-3" style="display: none;">
                <div class="flex items-center gap-2 text-primary font-semibold text-sm">
                    <i class="ti ti-calendar-time text-lg"></i>
                    <span>Plan Next Follow-up with Interested Lead</span>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-8">
                        <label class="form-label">
                            Next Follow-up Date <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="next_followup_date"
                            class="form-control next-followup-input-sold-no"
                            min="{{ today()->toDateString() }}"
                            value="{{ old('next_followup_date', $call->next_followup_date?->format('Y-m-d')) }}"
                        />
                        <p class="form-hint text-xs text-muted mt-1">Select date to follow up on this deal.</p>
                        @error('next_followup_date')
                            <div class="form-error mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- REMARKS (Mandatory across all decision paths)                             --}}
    {{-- ========================================================================= --}}
    <div>
        <label class="form-label font-semibold" for="remarks_{{ $formId }}">
            Remarks / Discussion Notes <span class="text-danger-500">*</span>
        </label>
        <textarea
            name="remarks"
            id="remarks_{{ $formId }}"
            class="form-control"
            rows="3"
            placeholder="Key discussion points, customer feedback, agreement details..."
            required
        >{{ old('remarks', $call->remarks) }}</textarea>
        @error('remarks')
            <div class="form-error mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<script>
window.setCallStatus = function(formId, val) {
    const container = document.getElementById(formId);
    if (!container) return;
    const input = container.querySelector('.call-status-select');
    if (input) input.value = val;

    container.querySelectorAll('.status-tile').forEach(tile => {
        tile.classList.remove('active', 'active-success', 'active-danger');
        if (tile.getAttribute('data-value') === val) {
            tile.classList.add(val === 'answered' ? 'active-success' : 'active-danger');
        }
    });

    if (window.syncCallFormVisibility) window.syncCallFormVisibility(container);
};

window.setInterest = function(formId, val) {
    const container = document.getElementById(formId);
    if (!container) return;
    const input = container.querySelector('.interest-select');
    if (input) input.value = val;

    container.querySelectorAll('.interest-tile').forEach(tile => {
        tile.classList.remove('active', 'active-success', 'active-danger');
        if (tile.getAttribute('data-value') === val) {
            tile.classList.add(val === '1' ? 'active-success' : 'active-danger');
        }
    });

    if (window.syncCallFormVisibility) window.syncCallFormVisibility(container);
};

window.setItemSold = function(formId, val) {
    const container = document.getElementById(formId);
    if (!container) return;
    const input = container.querySelector('.item-sold-select');
    if (input) input.value = val;

    container.querySelectorAll('.item-sold-tile').forEach(tile => {
        tile.classList.remove('active', 'active-success', 'active-danger');
        if (tile.getAttribute('data-value') === val) {
            tile.classList.add(val === '1' ? 'active-success' : 'active');
        }
    });

    if (window.syncCallFormVisibility) window.syncCallFormVisibility(container);
};

window.setReason = function(formId, text) {
    const container = document.getElementById(formId);
    if (!container) return;
    const reasonInput = container.querySelector('.reason-input');
    if (reasonInput) {
        reasonInput.value = text;
        reasonInput.focus();
    }
};

window.triggerCallFileUpload = function(formId) {
    const fileInput = document.getElementById('invoice_file_input_' + formId);
    if (fileInput) fileInput.click();
};

window.handleCallFileSelect = function(formId, input) {
    if (input.files && input.files[0]) {
        const label = document.querySelector('.upload-label-' + formId);
        if (label) {
            label.innerHTML = '<span class="text-success font-semibold"><i class="ti ti-circle-check"></i> ' + input.files[0].name + '</span>';
        }
    }
};

window.syncCallFormVisibility = function(container) {
    if (!container) return;

    const statusInput = container.querySelector('.call-status-select');
    const interestInput = container.querySelector('.interest-select');
    const itemSoldInput = container.querySelector('.item-sold-select');

    const status = statusInput ? statusInput.value : '';
    const interest = interestInput ? interestInput.value : '';
    const itemSold = itemSoldInput ? itemSoldInput.value : '';

    const branchAnswered = container.querySelector('.branch-answered');
    const branchNotAnswered = container.querySelector('.branch-not-answered');
    const subbranchNotInterested = container.querySelector('.subbranch-not-interested');
    const subbranchInterested = container.querySelector('.subbranch-interested');
    const subbranchItemSold = container.querySelector('.subbranch-item-sold');
    const subbranchItemNotSold = container.querySelector('.subbranch-item-not-sold');

    const nextFollowupInput = container.querySelector('.next-followup-input');
    const nextFollowupInputSoldNo = container.querySelector('.next-followup-input-sold-no');
    const reasonInput = container.querySelector('.reason-input');
    const invoiceNumberInput = container.querySelector('.invoice-number-input');
    const invoiceFileInput = container.querySelector('.invoice-file-input');

    if (status === 'not_answered') {
        if (branchNotAnswered) branchNotAnswered.style.display = 'block';
        if (branchAnswered) branchAnswered.style.display = 'none';

        if (nextFollowupInput) {
            nextFollowupInput.disabled = false;
            nextFollowupInput.required = true;
        }
        if (nextFollowupInputSoldNo) nextFollowupInputSoldNo.disabled = true;
        if (interestInput) interestInput.disabled = true;
        if (reasonInput) reasonInput.disabled = true;
        if (itemSoldInput) itemSoldInput.disabled = true;
        if (invoiceNumberInput) invoiceNumberInput.disabled = true;
        if (invoiceFileInput) invoiceFileInput.disabled = true;

    } else if (status === 'answered') {
        if (branchNotAnswered) branchNotAnswered.style.display = 'none';
        if (branchAnswered) branchAnswered.style.display = 'block';

        if (nextFollowupInput) nextFollowupInput.disabled = true;
        if (interestInput) {
            interestInput.disabled = false;
        }

        if (interest === '0') {
            if (subbranchNotInterested) subbranchNotInterested.style.display = 'block';
            if (subbranchInterested) subbranchInterested.style.display = 'none';

            if (reasonInput) {
                reasonInput.disabled = false;
                reasonInput.required = true;
            }
            if (itemSoldInput) itemSoldInput.disabled = true;
            if (invoiceNumberInput) invoiceNumberInput.disabled = true;
            if (invoiceFileInput) invoiceFileInput.disabled = true;
            if (nextFollowupInputSoldNo) nextFollowupInputSoldNo.disabled = true;

        } else if (interest === '1') {
            if (subbranchNotInterested) subbranchNotInterested.style.display = 'none';
            if (subbranchInterested) subbranchInterested.style.display = 'block';

            if (reasonInput) reasonInput.disabled = true;
            if (itemSoldInput) {
                itemSoldInput.disabled = false;
            }

            if (itemSold === '1') {
                if (subbranchItemSold) subbranchItemSold.style.display = 'block';
                if (subbranchItemNotSold) subbranchItemNotSold.style.display = 'none';

                if (invoiceNumberInput) {
                    invoiceNumberInput.disabled = false;
                    invoiceNumberInput.required = true;
                }
                if (invoiceFileInput) invoiceFileInput.disabled = false;
                if (nextFollowupInputSoldNo) nextFollowupInputSoldNo.disabled = true;

            } else if (itemSold === '0') {
                if (subbranchItemSold) subbranchItemSold.style.display = 'none';
                if (subbranchItemNotSold) subbranchItemNotSold.style.display = 'block';

                if (invoiceNumberInput) invoiceNumberInput.disabled = true;
                if (invoiceFileInput) invoiceFileInput.disabled = true;
                if (nextFollowupInputSoldNo) {
                    nextFollowupInputSoldNo.disabled = false;
                    nextFollowupInputSoldNo.required = true;
                }
            } else {
                if (subbranchItemSold) subbranchItemSold.style.display = 'none';
                if (subbranchItemNotSold) subbranchItemNotSold.style.display = 'none';
                if (invoiceNumberInput) invoiceNumberInput.disabled = true;
                if (invoiceFileInput) invoiceFileInput.disabled = true;
                if (nextFollowupInputSoldNo) nextFollowupInputSoldNo.disabled = true;
            }
        } else {
            if (subbranchNotInterested) subbranchNotInterested.style.display = 'none';
            if (subbranchInterested) subbranchInterested.style.display = 'none';
            if (reasonInput) reasonInput.disabled = true;
            if (itemSoldInput) itemSoldInput.disabled = true;
            if (invoiceNumberInput) invoiceNumberInput.disabled = true;
            if (invoiceFileInput) invoiceFileInput.disabled = true;
            if (nextFollowupInputSoldNo) nextFollowupInputSoldNo.disabled = true;
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.call-details-form-container').forEach(function(container) {
        if (window.syncCallFormVisibility) window.syncCallFormVisibility(container);
    });
});
</script>
