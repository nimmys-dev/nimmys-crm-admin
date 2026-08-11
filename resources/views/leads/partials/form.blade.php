{{--
    Shared by create and edit. Form components repopulate from old(), so a
    failed validation never loses what was typed — including the editor,
    whose value is pushed back to the textarea on every keystroke.
--}}

<div class="grid grid-cols-12 gap-4">

    <x-form.input name="name" label="Lead name" :value="$lead->name" required />

    <x-form.input name="phone" label="Phone" :value="$lead->phone" required />


    <x-form.select
        name="source" label="Source" :options="$sourceOptions"
        :selected="$lead->source?->value ?? \App\Enums\LeadSource::Call->value" placeholder="Not recorded"
    />


    @if ($canAssign)
        <x-form.select
            name="assigned_to" label="Assign To" :options="$assignableUsers"
            :selected="$lead->assigned_to ?? Auth::id()" placeholder="Unassigned"
            hint="Only active users with Lead access can be given leads."
        />
    @else
        {{-- Employees own what they create; the field is not theirs to set. --}}
        <div class="col-span-12 md:col-span-6">
            <label class="form-label">Assign To</label>
            <input type="text" class="form-control" value="{{ $lead->owner?->name ?? auth()->user()->name }}" readonly />
            <small class="text-muted">Leads you create are assigned to you.</small>
        </div>
    @endif

    {{-- <x-form.select
        name="status" label="Status" :options="$statusOptions"
        :selected="$lead->status?->value" :placeholder="false" required
    /> --}}

    {{-- <x-form.input
        name="lost_reason" label="Reason (if lost)" :value="$lead->lost_reason"
        hint="Required when the status is Lost."
    /> --}}

    <div class="col-span-12">
        <h6 class="form-section">Notes</h6>
    </div>

    <x-form.editor
        name="description"
        label="Description"
        :value="$lead->description"
        hint="Background, requirements, anything the next person should know."
    />

</div>
