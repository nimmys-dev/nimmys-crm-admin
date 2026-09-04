{{--
    Searchable, paginated call history plus the "log a call" modal and button.

    Injected by CallHistoryComposer.

    Expects: $lead, $callHistory, $callFilters, $callHasActiveFilters,
             $callStatusOptions, $callerOptions, $canAttributeCall
--}}

<div class="card p-4 shadow-sm border-0 rounded-3 bg-white">
    <!-- Header Section -->
    <div class="d-flex align-items-center gap-3 mb-3">
        <h4 class="mb-0 fw-bold text-dark fs-5">Telecalling Details</h4>
        
        @can('create', [App\Models\CallDetail::class, $lead])
            <button
                type="button"
                class="btn text-white font-medium px-3 py-1.5 rounded-3 shadow-sm border-0"
                style="background-color: #14b8a6; white-space: nowrap; font-size: 0.875rem;"
                onclick="openModal('logCallModal')"
            >
                Add call
            </button>
        @endcan
    </div>

    <!-- Table Section -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" style="border-color: #edf2f7;">
            <thead>
                <tr style="background-color: #ffffff;">
                    <th class="fw-bold text-dark py-3 px-3" style="width: 18%;">Call Details</th>
                    <th class="fw-bold text-dark py-3 px-3" style="width: 15%;">Interest</th>
                    <th class="fw-bold text-dark py-3 px-3" style="width: 37%;">Remarks</th>
                    <th class="fw-bold text-dark py-3 px-3" style="width: 15%;">Called By</th>
                    <th class="fw-bold text-dark py-3 px-3" style="width: 15%;">Called Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($callHistory as $call)
                    <tr>
                        <!-- 1. Call Details (Status) -->
                        <td class="text-secondary py-3 px-3">
                            {{ ucfirst(str_replace('_', ' ', $call->call_status->value ?? $call->call_status)) }}
                        </td>

                        <!-- 2. Interest -->
                        <td class="text-secondary py-3 px-3">
                            @if ($call->isNotAnswered())
                                —
                            @elseif ($call->interest === true)
                                Yes
                            @elseif ($call->interest === false)
                                No
                            @else
                                —
                            @endif
                        </td>

                        <!-- 3. Remarks -->
                        <td class="text-secondary py-3 px-3">
                            {{ filled($call->remarks) ? strip_tags($call->remarks) : '—' }}
                        </td>

                        <!-- 4. Called By -->
                        <td class="text-secondary py-3 px-3">
                            {{ $call->caller?->name ?? '—' }}
                        </td>

                        <!-- 5. Called Date & Time -->
                        <td class="text-secondary py-3 px-3">
                            <div>{{ $call->called_date->format('d-m-Y') }}</div>
                            <div class="text-muted small mt-1">{{ $call->calledAt()->format('g:i a') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            {{ $callHasActiveFilters ? 'No calls match this search.' : 'No calls logged for this lead yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- LOG CALL MODAL DIALOG                                                     --}}
{{-- ========================================================================= --}}
@can('create', [App\Models\CallDetail::class, $lead])
    <div id="logCallModal" class="custom-modal" style="display: none;">
        <div class="custom-modal-overlay" onclick="closeModal('logCallModal')"></div>

        <div class="custom-modal-dialog custom-modal-dialog-lg">
            <!-- Modal Header -->
            <div class="custom-modal-header flex items-center justify-between p-4 border-b">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                        <i class="ti ti-phone-outgoing"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-base font-bold">Add Telecall Details</h5>
                        <p class="text-xs text-muted mb-0">Record call outcome for <span class="font-semibold text-body">{{ $lead->name }}</span> ({{ $lead->reference }})</p>
                    </div>
                </div>

                <button type="button" class="btn-close" onclick="closeModal('logCallModal')"></button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="{{ route('leads.calls.store', $lead) }}" id="logCallForm">
                @csrf

                <div class="custom-modal-body p-6 space-y-4">
                    
                    <!-- 1. Call Details -->
                    <div class="grid grid-cols-3 items-center gap-4">
                        <label for="call_status" class="text-sm font-medium text-gray-700">
                            Call Details<span class="text-red-500">*</span>
                        </label>
                        <select id="call_status" name="call_status" onchange="toggleTelecallFields()" class="col-span-2 form-select w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">--Select--</option>
                            @foreach(App\Enums\CallStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('call_status', App\Enums\CallStatus::Answered->value) == $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Interest -->
                    <div id="interest_field_wrapper" class="grid grid-cols-3 items-center gap-4">
                        <label for="interest" class="text-sm font-medium text-gray-700">
                            Interest<span class="text-red-500">*</span>
                        </label>
                        <select id="interest" name="interest" onchange="toggleTelecallFields()" class="col-span-2 form-select w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">--Select--</option>
                            <option value="Yes" {{ old('interest', 'Yes') == 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('interest') == 'No' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <!-- 3. Is item sold? (If Answered & Interest = Yes) -->
                    <div id="is_sold_field_wrapper" class="grid grid-cols-3 items-center gap-4" style="display: none;">
                        <label for="is_item_sold" class="text-sm font-medium text-gray-700">
                            Is item sold?<span class="text-red-500">*</span>
                        </label>
                        <select id="is_item_sold" name="is_item_sold" onchange="toggleTelecallFields()" class="col-span-2 form-select w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="0" {{ old('is_item_sold', '0') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('is_item_sold') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <!-- 4. Invoice Number Field -->
                    <div id="invoice_field_wrapper" class="grid grid-cols-3 items-center gap-4" style="display: none;">
                        <label for="invoice_number" class="text-sm font-medium text-gray-700">
                            Invoice Number<span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="invoice_number"
                            name="invoice_number"
                            value="{{ old('invoice_number') }}"
                            placeholder="Enter Invoice Number"
                            class="col-span-2 form-input w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2"
                        />
                    </div>

                    <!-- 5. Reason (If Answered & Interest = No) -->
                    <div id="reason_field_wrapper" class="grid grid-cols-3 items-center gap-4" style="display: none;">
                        <label for="reason" class="text-sm font-medium text-gray-700">
                            Reason<span class="text-red-500">*</span>
                        </label>
                        <select id="reason" name="reason" class="col-span-2 form-select w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">--Select--</option>
                            <option value="Not eligible for finance" {{ old('reason') == 'Not eligible for finance' ? 'selected' : '' }}>Not eligible for finance</option>
                            <option value="Not planning now" {{ old('reason') == 'Not planning now' ? 'selected' : '' }}>Not planning now</option>
                            <option value="Price is higher" {{ old('reason') == 'Price is higher' ? 'selected' : '' }}>Price is higher</option>
                            <option value="Purchased from another store" {{ old('reason') == 'Purchased from another store' ? 'selected' : '' }}>Purchased from another store</option>
                        </select>
                    </div>

                    <!-- 6. Next FollowUp Date -->
                    <div id="followup_field_wrapper" class="grid grid-cols-3 items-center gap-4">
                        <label for="next_followup_date" class="text-sm font-medium text-gray-700">
                            Next FollowUp Date <span class="text-red-500">*</span>
                        </label>
                        <div class="col-span-2 flex items-center rounded-md border border-gray-300 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 overflow-hidden">
                            <span class="inline-flex items-center px-3 bg-gray-100 text-gray-500 border-r border-gray-300">
                                <i class="ti ti-calendar text-lg"></i>
                            </span>
                            <input
                                type="date"
                                id="next_followup_date"
                                name="next_followup_date"
                                value="{{ old('next_followup_date', date('Y-m-d')) }}"
                                class="w-full border-0 focus:ring-0 text-sm p-2 text-gray-700"
                            />
                        </div>
                    </div>

                    <!-- 7. Remarks -->
                    <div class="grid grid-cols-3 items-start gap-4">
                        <label for="remarks" class="text-sm font-medium text-gray-700 pt-2">
                            Remarks<span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="3"
                            placeholder="Remarks"
                            class="col-span-2 form-textarea w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2"
                        >{{ old('remarks') }}</textarea>
                    </div>

                </div>

                <div class="custom-modal-footer bg-light/30 p-4 border-t flex justify-start gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 text-sm font-medium rounded-md shadow-sm">
                        Submit
                    </button>
                    <button type="button" class="btn btn-secondary px-4 py-2 text-sm font-medium rounded-md" onclick="closeModal('logCallModal')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleTelecallFields() {
            const callStatus = document.getElementById('call_status').value;
            const interest = document.getElementById('interest').value;
            const isSold = document.getElementById('is_item_sold').value;

            const interestWrapper = document.getElementById('interest_field_wrapper');
            const isSoldWrapper = document.getElementById('is_sold_field_wrapper');
            const invoiceWrapper = document.getElementById('invoice_field_wrapper');
            const reasonWrapper = document.getElementById('reason_field_wrapper');
            const followupWrapper = document.getElementById('followup_field_wrapper');

            // Reset display
            interestWrapper.style.display = 'none';
            isSoldWrapper.style.display = 'none';
            invoiceWrapper.style.display = 'none';
            reasonWrapper.style.display = 'none';
            followupWrapper.style.display = 'none';

            if (callStatus === '{{ App\Enums\CallStatus::Answered->value }}') {
                interestWrapper.style.display = 'grid';

                if (interest === 'Yes') {
                    isSoldWrapper.style.display = 'grid';

                    if (isSold === '1') {
                        invoiceWrapper.style.display = 'grid';
                    } else {
                        followupWrapper.style.display = 'grid';
                    }
                } else if (interest === 'No') {
                    reasonWrapper.style.display = 'grid';
                }
            } else if (callStatus === '{{ App\Enums\CallStatus::NotAnswered->value }}') {
                followupWrapper.style.display = 'grid';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleTelecallFields();

            @if ($errors->any() && old('call_status'))
                if (typeof openModal === 'function') {
                    openModal('logCallModal');
                }
            @endif
        });
        function openCallModal() {
        // Reset values to defaults as shown in screenshot
        document.getElementById('call_status').value = '{{ App\Enums\CallStatus::Answered->value }}';
        document.getElementById('interest').value = 'Yes';
        document.getElementById('is_item_sold').value = '0';
        
        // Set Today's Date dynamically
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('next_followup_date').value = today;

        // Toggle visible fields
        toggleTelecallFields();

        // Show modal function (if applicable)
        if (typeof openModal === 'function') {
            openModal('logCallModal');
        } else {
            document.getElementById('logCallModal').style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleTelecallFields();
    });
    </script>
@endcan
@foreach ($callHistory as $call)
    @can('delete', $call)
        <x-delete-modal
            :id="'delete-call-' . $call->id"
            :action="route('leads.calls.destroy', [$lead, $call])"
            title="Delete call record"
            :message="'Remove the ' . $call->calledAt()->format('d-M-Y') . ' call from this history? The lead itself is kept.'"
            confirm="Remove call"
        />
    @endcan
@endforeach
<script>
document.getElementById('logCallForm').addEventListener('submit', function (e) {
    const form = this;
    const button = document.getElementById('logCallSubmitBtn');

    // തുടർച്ചയായ ക്ലിക്കുകൾ തടയുന്നു
    if (form.dataset.submitted === 'true') {
        e.preventDefault();
        return false;
    }

    // ഫോം സബ്മിറ്റ് ആയി എന്ന് മാർക്ക് ചെയ്യുന്നു
    form.dataset.submitted = 'true';

    // ഒറ്റ ക്ലിക്കിൽ ബട്ടൺ ഡിസേബിൾ ചെയ്യുകയും ഹൈഡ് ചെയ്യുകയും ചെയ്യുന്നു
    button.disabled = true;
    button.style.display = 'none';
});
</script>