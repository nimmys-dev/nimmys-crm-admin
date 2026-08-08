{{--
    Most recently created shops. Admin-only — a Manager runs a single shop,
    so a "recent shops" list would be meaningless for them.

    Expects: $recentShops
--}}

<x-dashboard-widget
    title="Recently created shops"
    icon="ti ti-building-store"
    action-label="View all"
    :action-href="route('shops.index')"
>
    <x-dashboard-table
        :rows="$recentShops"
        :headers="['Shop', 'Code', 'Staff', 'Status', 'Created']"
        empty-message="No shops created yet."
        empty-icon="ti ti-building-store"
    >
        @foreach ($recentShops as $shop)
            <tr>
                <td>
                    <a href="{{ route('shops.show', $shop) }}" class="font-medium">{{ $shop->name }}</a>
                    @if ($shop->city)
                        <p class="m-0 text-muted text-sm">{{ $shop->city }}</p>
                    @endif
                </td>

                <td><span class="text-muted">{{ $shop->code }}</span></td>

                <td class="tabular">{{ $shop->staff_count }}</td>

                <td><x-status-badge :status="$shop->status->value" /></td>

                <td>{{ $shop->created_at->format('j M Y') }}</td>
            </tr>
        @endforeach
    </x-dashboard-table>
</x-dashboard-widget>
