{{--
    Shared by create and edit. The parent supplies $shop, $managerOptions and
    $statusOptions; the form components repopulate from old() automatically,
    so a failed validation never loses what was typed.
--}}

<div class="grid grid-cols-12 gap-4">

    <x-form.input
        name="code"
        label="Shop code"
        :value="$shop->code"
        required
        hint="Letters, numbers, hyphens and underscores. Stored uppercase."
    />

    <x-form.input name="name" label="Shop name" :value="$shop->name" required />

    <!-- <x-form.select
        name="manager_id"
        label="Manager"
        :options="$managerOptions"
        :selected="$shop->manager_id"
        placeholder="Unassigned"
        hint="Only Admins and Managers can run a shop."
    /> -->

    <x-form.select
        name="status"
        label="Status"
        :options="$statusOptions"
        :selected="$shop->status?->value"
        :placeholder="false"
        required
    />

    <!-- <x-form.input name="email" type="email" label="Email" :value="$shop->email" />

    <x-form.input name="phone" label="Phone" :value="$shop->phone" />

    <x-form.input name="address_line" label="Address" :value="$shop->address_line" col="col-span-12" />

    <x-form.input name="city" label="City" :value="$shop->city" col="col-span-12 md:col-span-3" />

    <x-form.input name="state" label="State" :value="$shop->state" col="col-span-12 md:col-span-3" />

    <x-form.input name="postal_code" label="Postal code" :value="$shop->postal_code" col="col-span-12 md:col-span-3" />

    <x-form.input name="country" label="Country" :value="$shop->country" col="col-span-12 md:col-span-3" /> -->

    <!-- <x-form.input
        name="opened_on"
        type="date"
        label="Opening date"
        :value="$shop->opened_on?->format('Y-m-d')"
    /> -->

    <!-- <x-form.textarea name="notes" label="Notes" :value="$shop->notes" rows="4" /> -->

</div>
