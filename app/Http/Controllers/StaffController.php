<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Staff\StaffIndexRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Shop;
use App\Models\User;
use App\Services\StaffPhotoService;
use App\Support\EmployeeCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Staff Management — Admin only.
 *
 * Authorization is enforced by `can:staff.manage` on the route group and
 * again inside each form request. Filtering lives in the model's scopes and
 * file handling in StaffPhotoService, so this controller stays thin.
 */
class StaffController extends Controller
{
    public function __construct(private readonly StaffPhotoService $photos) {}

    public function index(StaffIndexRequest $request): View
    {
        $filters = $request->filters();

        $staff = User::query()
            ->with('shop:id,name')
            ->search($filters['q'])
            ->filterRole($filters['role'])
            ->filterStatus($filters['status'])
            ->filterShop($filters['shop_id'])
            ->sorted($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('staff.index', [
            'pageTitle' => 'Staff Management',
            'breadcrumbs' => [['label' => 'Staff']],
            'staff' => $staff,
            'filters' => $filters,
            'hasActiveFilters' => $request->hasActiveFilters(),
            'roleOptions' => UserRole::options(),
            'statusOptions' => UserStatus::options(),
            'shopOptions' => $this->shopOptions(),
            'photos' => $this->photos,
        ]);
    }

    public function create(): View
    {
        return view('staff.create', [
            'pageTitle' => 'Add Staff',
            'breadcrumbs' => [
                ['label' => 'Staff', 'route' => 'staff.index'],
                ['label' => 'Add'],
            ],
            'staff' => new User(['status' => UserStatus::Active, 'role' => UserRole::Employee]),
            'nextCode' => EmployeeCode::next(),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        // staffAttributes() drops the Admin-only toggles when the current
        // user may not set them.
        $data = $request->staffAttributes();
        $data['password'] = $request->validated('password');

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->photos->store($request->file('photo'));
        }

        // The code is allocated and the row inserted inside one transaction,
        // so concurrent creates cannot claim the same number.
        $staff = EmployeeCode::withNext(function (string $code) use ($data) {
            return User::create([...$data, 'employee_code' => $code]);
        });

        return redirect()
            ->route('staff.show', $staff)
            ->with('success', "{$staff->name} was added as {$staff->role->label()} ({$staff->employee_code}).");
    }

    public function show(User $staff): View
    {
        $staff->load(['shop:id,name,code', 'managedShop:id,name,code']);

        return view('staff.show', [
            'pageTitle' => $staff->name,
            'breadcrumbs' => [
                ['label' => 'Staff', 'route' => 'staff.index'],
                ['label' => $staff->employee_code ?? $staff->name],
            ],
            'staff' => $staff,
            'photoUrl' => $this->photos->url($staff->photo),
        ]);
    }

    public function edit(User $staff): View
    {
        return view('staff.edit', [
            'pageTitle' => "Edit {$staff->name}",
            'breadcrumbs' => [
                ['label' => 'Staff', 'route' => 'staff.index'],
                ['label' => $staff->employee_code ?? $staff->name, 'route' => null],
                ['label' => 'Edit'],
            ],
            'staff' => $staff,
            'photoUrl' => $this->photos->url($staff->photo),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $data = $request->staffAttributes();

        // A blank password field means "leave it alone".
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->photos->store($request->file('photo'), $staff->photo);
        }

        $staff->update($data);

        return redirect()
            ->route('staff.show', $staff)
            ->with('success', "{$staff->name} was updated.");
    }

    /**
     * Soft delete. The row survives so leads, tasks and login history stay
     * resolvable, and the account can no longer authenticate — Laravel's
     * user provider applies the soft-delete scope.
     */
    public function destroy(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()->id) {
            return back()->with('danger', 'You cannot delete your own account.');
        }

        if ($this->isLastActiveAdmin($staff)) {
            return back()->with('danger', 'This is the last active Admin. Promote another before deleting this one.');
        }

        $name = $staff->name;

        // Tokens are revoked explicitly: a soft delete leaves the row, and
        // an issued mobile token would otherwise keep working.
        $staff->tokens()->delete();
        $staff->delete();

        return redirect()
            ->route('staff.index')
            ->with('success', "{$name} was removed.");
    }

    /**
     * Delete just the profile photo, leaving the record intact.
     */
    public function destroyPhoto(User $staff): RedirectResponse
    {
        $this->photos->purgeFor($staff);

        return back()->with('success', 'Profile photo removed.');
    }

    /**
     * Removing the only remaining active Admin would leave the panel with no
     * one able to administer it.
     */
    private function isLastActiveAdmin(User $staff): bool
    {
        if (! $staff->isAdmin() || ! $staff->isActive()) {
            return false;
        }

        return User::query()
            ->role(UserRole::Admin)
            ->active()
            ->whereKeyNot($staff->id)
            ->doesntExist();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roleOptions' => UserRole::options(),
            'statusOptions' => UserStatus::options(),
            'shopOptions' => $this->shopOptions(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function shopOptions(): array
    {
        return Shop::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function resetPassword(User $member)
    {
        return view('staff.reset-password', compact('member'));
    }



    public function updatePassword(Request $request, User $member): RedirectResponse
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        // Hash::make ഉപയോഗിച്ച് പാസ്‌വേഡ് ഹാഷ് ചെയ്യുന്നു
        $member->password = Hash::make($request->password);

        // Explicit ആയി updated_at അപ്ഡേറ്റ് ആകാൻ
        $member->updated_at = now(); 

        // ഡാറ്റാബേസിൽ സേവ് ചെയ്യുന്നു
        $member->save();

        return redirect()
            ->route('staff.index')
            ->with('success', "Password reset successfully for {$member->name}.");
    }
}
