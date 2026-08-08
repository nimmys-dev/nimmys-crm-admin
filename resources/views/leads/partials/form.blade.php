{{--
    Shared by create and edit. Form components repopulate from old(), so a
    failed validation never loses what was typed — including the editor,
    whose value is pushed back to the textarea on every keystroke.
--}}

<div class="grid grid-cols-12 gap-4">

    <div class="col-span-12">
        <h6 class="form-section">Contact</h6>
    </div>

    <x-form.input name="name" label="Contact name" :value="$lead->name" required />

    <x-form.input name="company" label="Company" :value="$lead->company" />

    <x-form.input name="phone" label="Phone" :value="$lead->phone" required />

    <x-form.input name="alternate_phone" label="Alternate phone" :value="$lead->alternate_phone" />

    <x-form.input name="email" type="email" label="Email" :value="$lead->email" />

    <x-form.input name="city" label="City" :value="$lead->city" />

    <div class="col-span-12">
        <h6 class="form-section">Pipeline</h6>
    </div>

    <x-form.select
        name="status" label="Status" :options="$statusOptions"
        :selected="$lead->status?->value" :placeholder="false" required
    />

    <x-form.select
        name="priority" label="Priority" :options="$priorityOptions"
        :selected="$lead->priority?->value" :placeholder="false" required
    />

    <x-form.select
        name="source" label="Source" :options="$sourceOptions"
        :selected="$lead->source?->value" placeholder="Not recorded"
    />

    <x-form.input
        name="value" type="number" label="Estimated value" :value="$lead->value"
        step="0.01" min="0" hint="Expected deal size."
    />

    <x-form.select
        name="shop_id" label="Shop" :options="$shopOptions"
        :selected="$lead->shop_id" placeholder="Unassigned"
    />

    @if ($canAssign)
        <x-form.select
            name="assigned_to" label="Owner" :options="$assignableUsers"
            :selected="$lead->assigned_to" placeholder="Unassigned"
            hint="Only active users with Lead access can be given leads."
        />
    @else
        {{-- Employees own what they create; the field is not theirs to set. --}}
        <div class="col-span-12 md:col-span-6">
            <label class="form-label">Owner</label>
            <input type="text" class="form-control" value="{{ $lead->owner?->name ?? auth()->user()->name }}" readonly />
            <small class="text-muted">Leads you create are assigned to you.</small>
        </div>
    @endif

    <x-form.input
        name="lost_reason" label="Reason (if lost)" :value="$lead->lost_reason"
        hint="Required when the status is Lost."
    />

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
