<?php

namespace App\Http\Controllers;

use App\Enums\ShopStatus;
use App\Http\Requests\Shop\ShopIndexRequest;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Models\Shop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Shop Management — Admin only.
 *
 * Authorization is enforced by `can:shops.manage` on the route group and
 * again by each form request. Filtering lives in the model's query scopes,
 * so this controller only wires request to response.
 */
class ShopController extends Controller
{
    public function index(ShopIndexRequest $request): View
    {
        $filters = $request->filters();

        $shops = Shop::query()
            // Eager loaded to keep the listing at two queries regardless of
            // page size — the manager name is rendered per row.
            ->with('manager:id,name')
            ->withCount('staff')
            ->search($filters['q'])
            ->status($filters['status'])
            ->city($filters['city'])
            ->sorted($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('shops.index', [
            'pageTitle' => 'Shop Management',
            'breadcrumbs' => [['label' => 'Shops']],
            'shops' => $shops,
            'filters' => $filters,
            'hasActiveFilters' => $request->hasActiveFilters(),
            'statusOptions' => ShopStatus::options(),
            'cityOptions' => Shop::cityOptions(),
        ]);
    }

    public function create(): View
    {
        return view('shops.create', [
            'pageTitle' => 'Add Shop',
            'breadcrumbs' => [
                ['label' => 'Shops', 'route' => 'shops.index'],
                ['label' => 'Add'],
            ],
            'shop' => new Shop(['status' => ShopStatus::Active]),
            'managerOptions' => StoreShopRequest::managerOptions(),
            'statusOptions' => ShopStatus::options(),
        ]);
    }

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $shop = Shop::create($request->validated());

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', "Shop \"{$shop->name}\" was created.");
    }

    public function show(Shop $shop): View
    {
        $shop->load(['manager:id,name,email', 'staff:id,shop_id,name,email,role,status']);

        return view('shops.show', [
            'pageTitle' => $shop->name,
            'breadcrumbs' => [
                ['label' => 'Shops', 'route' => 'shops.index'],
                ['label' => $shop->code],
            ],
            'shop' => $shop,
        ]);
    }

    public function edit(Shop $shop): View
    {
        return view('shops.edit', [
            'pageTitle' => "Edit {$shop->name}",
            'breadcrumbs' => [
                ['label' => 'Shops', 'route' => 'shops.index'],
                ['label' => $shop->code, 'route' => null],
                ['label' => 'Edit'],
            ],
            'shop' => $shop,
            'managerOptions' => StoreShopRequest::managerOptions(),
            'statusOptions' => ShopStatus::options(),
        ]);
    }

    public function update(UpdateShopRequest $request, Shop $shop): RedirectResponse
    {
        $shop->update($request->validated());

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', "Shop \"{$shop->name}\" was updated.");
    }

    /**
     * Soft delete. Staff keep their history; users.shop_id is left intact
     * because the shop can be restored.
     */
    public function destroy(Shop $shop): RedirectResponse
    {
        $name = $shop->name;

        $shop->delete();

        return redirect()
            ->route('shops.index')
            ->with('success', "Shop \"{$name}\" was deleted.");
    }
}
