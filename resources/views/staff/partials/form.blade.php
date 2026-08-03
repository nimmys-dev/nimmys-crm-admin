{{--
    Shared by create and edit. The parent supplies $staff plus the option
    lists; form components repopulate from old() so a failed validation
    never loses what was typed.

    $isCreate distinguishes the two modes — the password is required when
    adding and optional when editing.
--}}

@php
    $isCreate = ! $staff->exists;
@endphp

<div class="grid grid-cols-12 gap-4">

    {{-- Identity --}}

    <div class="col-span-12">
        <h6 class="form-section">Identity</h6>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label class="form-label" for="field-employee-code">Employee code</label>
        <input
            type="text"
            id="field-employee-code"
            class="form-control"
            value="{{ $staff->employee_code ?? $nextCode ?? '' }}"
            readonly
        />
        <small class="text-muted">
            {{ $isCreate ? 'Generated automatically when you save.' : 'Assigned when this member was added.' }}
        </small>
    </div>

    <x-form.input name="name" label="Full name" :value="$staff->name" required />

    <x-form.select
        name="role"
        label="Role"
        :options="$roleOptions"
        :selected="$staff->role?->value"
        :placeholder="false"
        required
        hint="Employees can sign in on mobile only."
    />

    <x-form.select
        name="shop_id"
        label="Shop"
        :options="$shopOptions"
        :selected="$staff->shop_id"
        placeholder="Unassigned"
        hint="Admins are organisation-wide and need no shop."
    />

    {{-- Contact --}}

    <div class="col-span-12">
        <h6 class="form-section">Contact</h6>
    </div>

    <x-form.input name="email" type="email" label="Email" :value="$staff->email" required />

    <x-form.input name="phone" label="Mobile" :value="$staff->phone" required />

    <x-form.input name="alternate_phone" label="Alternate mobile" :value="$staff->alternate_phone" />

    {{-- Employment --}}

    <div class="col-span-12">
        <h6 class="form-section">Employment</h6>
    </div>

    <x-form.input
        name="joining_date"
        type="date"
        label="Joining date"
        :value="$staff->joining_date?->format('Y-m-d')"
    />

    <x-form.input
        name="salary"
        type="number"
        label="Salary"
        :value="$staff->salary"
        step="0.01"
        min="0"
        hint="Monthly, in your local currency."
    />

    <x-form.select
        name="status"
        label="Status"
        :options="$statusOptions"
        :selected="$staff->status?->value"
        :placeholder="false"
        required
    />

    <x-form.file
        name="photo"
        label="Profile photo"
        :current-url="$photoUrl ?? null"
        :remove-action="$staff->exists && $staff->photo ? route('staff.photo.destroy', $staff) : null"
        hint="JPG, PNG or WebP. Max 2 MB, at least 100×100."
    />

    <x-form.textarea name="description" label="Description" :value="$staff->description" rows="3" />

    {{-- Access --}}

    <div class="col-span-12">
        <h6 class="form-section">Access</h6>
    </div>

    <x-form.input
        name="password"
        type="password"
        label="Password"
        :required="$isCreate"
        autocomplete="new-password"
        :hint="$isCreate ? null : 'Leave blank to keep the current password.'"
    />

    <x-form.input
        name="password_confirmation"
        type="password"
        label="Confirm password"
        :required="$isCreate"
        autocomplete="new-password"
    />

</div>
