{{--
    Log a call against a lead.

    Shared by the inline form on the Lead detail page and the standalone edit
    page. $call is an existing record when editing and a blank model when
    adding, so the form components repopulate identically in both modes.

    Expects: $lead, $call, $statusOptions, $callerOptions, $canAttribute
--}}

<div class="grid grid-cols-12 gap-4">

    <x-form.select
        name="call_status"
        label="Call status"
        :options="$statusOptions"
        :selected="$call->call_status?->value"
        :placeholder="false"
        required
        col="col-span-12 md:col-span-6"
    />

    @if ($canAttribute)
        <x-form.select
            name="called_by"
            label="Called by"
            :options="$callerOptions"
            :selected="$call->called_by ?? auth()->id()"
            placeholder="Unattributed"
            col="col-span-12 md:col-span-6"
            hint="Defaults to you."
        />
    @else
        {{-- Employees log calls as themselves; the field is not theirs to set. --}}
        <div class="col-span-12 md:col-span-6">
            <label class="form-label">Called by</label>
            <input type="text" class="form-control" value="{{ $call->caller?->name ?? auth()->user()->name }}" readonly />
        </div>
    @endif

    <x-form.input
        name="called_date"
        type="date"
        label="Call date"
        :value="$call->called_date?->format('Y-m-d') ?? today()->format('Y-m-d')"
        required
        col="col-span-12 md:col-span-4"
    />

    <x-form.input
        name="called_time"
        type="time"
        label="Call time"
        :value="$call->called_time ? substr($call->called_time, 0, 5) : now()->format('H:i')"
        required
        col="col-span-12 md:col-span-4"
    />

    <x-form.input
        name="duration"
        type="number"
        label="Duration"
        :value="$call->duration"
        min="0"
        step="1"
        hint="In seconds. Leave blank if not connected."
        col="col-span-12 md:col-span-4"
    />

    <x-form.input
        name="next_followup_date"
        type="date"
        label="Next follow-up date"
        :value="$call->next_followup_date?->format('Y-m-d')"
        hint="Leave blank if no further contact is planned."
        col="col-span-12 md:col-span-6"
    />

    <x-form.textarea
        name="remarks"
        label="Remarks"
        :value="$call->remarks"
        rows="3"
        col="col-span-12"
        hint="What was discussed."
    />

</div>
