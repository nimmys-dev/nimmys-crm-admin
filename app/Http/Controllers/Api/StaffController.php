<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Support\EmployeeCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;



class StaffController extends Controller
{

    public function staffList(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = trim($request->get('search', ''));

        $staff = User::select(
                'id',
                'employee_code',
                'name',
                'email',
                'phone',
                'shop_id',
                'role',
                'status',
                'photo',
                'created_at'
            )
            ->whereIn('role', ['manager', 'employee'])
            ->when($search !== '', function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('employee_code', 'LIKE', "%{$search}%")
                        ->orWhere('shop_id', 'LIKE', "%{$search}%")
                        ->orWhere('status', 'LIKE', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        $staff->getCollection()->transform(function ($item) {

            $item->photo_url = $item->photo
                ? url(Storage::url($item->photo))
                : null;

            return $item;
        });

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Staff list fetched successfully.',
            'data' => $staff->items(),

            'pagination' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
                'from' => $staff->firstItem(),
                'to' => $staff->lastItem(),
                'next_page_url' => $staff->nextPageUrl(),
                'previous_page_url' => $staff->previousPageUrl(),
            ]
        ], 200);
    }
    public function createStaff(StoreStaffRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        // Photo Upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('staff-photos', 'public');
        }

        // Auto Generate Employee Code
        $staff = EmployeeCode::withNext(function (string $code) use ($data) {
            $data['employee_code'] = $code;
            return User::create($data);
        });

        return response()->json([
            'status' => true,
            'status_code' => 201,
            'message' => 'Staff created successfully.',
            'data' => [
                'id' => $staff->id,
                'employee_code' => $staff->employee_code,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'shop_id' => $staff->shop_id,
                'role' => $staff->role,
                'status' => $staff->status,
                'photo' => $staff->photo,
                'photo_url' => $staff->photo? url(Storage::url($staff->photo)): null,
            ]
        ], 201);
    }
    public function getBranches(): JsonResponse
    {
        $shops = Shop::select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Shop status fetched successfully',
            'data' => $shops
        ], 200);
    }

    public function viewStaff($id): JsonResponse
    {
        $staff = User::find($id);
        if (!$staff) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Staff not found.',
            ], 404);
        }
        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Staff details fetched successfully.',
            'data' => [
                'id' => $staff->id,
                'employee_code' => $staff->employee_code,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'shop_id' => $staff->shop_id,
                'alternate_phone' => $staff->alternate_phone,
                'joining_date' => $staff->joining_date,
                'salary' => $staff->salary,
                'increment_date' => $staff->increment_date,
                'increment_amount' => $staff->increment_amount,
                'increment_notification' => $staff->increment_notification,
                'lead_module_access' => $staff->lead_module_access,
                'description' => $staff->description,
                'role' => $staff->role,
                'status' => $staff->status,
                'photo' => $staff->photo,
                'photo_url' => $staff->photo
                    ? url(Storage::url($staff->photo))
                    : null,
                'created_at' => $staff->created_at,
                'updated_at' => $staff->updated_at,
            ]
        ], 200);
    }

    public function updateStaff(Request $request, $id): JsonResponse
    {
        $staff = User::find($id);

        if (!$staff) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Staff not found.',
            ], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:191',
            'email' => 'sometimes|email|max:191|unique:users,email,' . $id,
            'phone' => 'sometimes|nullable|string|max:20',
            'alternate_phone' => 'sometimes|nullable|string|max:20',
            'shop_id' => 'sometimes|nullable|integer',
            'role' => 'sometimes|in:admin,manager,employee',
            'status' => 'sometimes|in:active,inactive,suspended',
            'joining_date' => 'sometimes|nullable',
            'salary' => 'sometimes|nullable|numeric',
            'increment_date' => 'sometimes|nullable',
            'increment_amount' => 'sometimes|nullable|numeric',
            'increment_notification' => 'sometimes|boolean',
            'lead_module_access' => 'sometimes|boolean',
            'description' => 'sometimes|nullable|string',
            'password' => 'sometimes|nullable|min:8',
            'password_confirmation' => 'sometimes|same:password',
            'photo' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Password
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        // Password confirmation database-il save cheyyanda
        unset($data['password_confirmation']);

        // Photo upload
        if ($request->hasFile('photo')) {

            // Delete old photo
            if (
                $staff->photo &&
                Storage::disk('public')->exists($staff->photo)
            ) {
                Storage::disk('public')->delete($staff->photo);
            }
            // Store new photo
            $data['photo'] = $request->file('photo')
                ->store('staff-photos', 'public');
        }

        // Update staff
        $staff->update($data);
        // Get latest data
        $staff->refresh();
        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Staff updated successfully.',
            'data' => [
                'id' => $staff->id,
                'employee_code' => $staff->employee_code,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'alternate_phone' => $staff->alternate_phone,
                'shop_id' => $staff->shop_id,
                'role' => $staff->role,
                'status' => $staff->status,
                'joining_date' => $staff->joining_date,
                'salary' => $staff->salary,
                'increment_date' => $staff->increment_date,
                'increment_amount' => $staff->increment_amount,
                'increment_notification' => $staff->increment_notification,
                'lead_module_access' => $staff->lead_module_access,
                'description' => $staff->description,
                'photo' => $staff->photo,
                'photo_url' => $staff->photo
                    ? url(Storage::url($staff->photo))
                    : null,
                'updated_at' => $staff->updated_at,
            ]
        ], 200);
    }

    public function deleteStaff($id): JsonResponse
    {
        $staff = User::find($id);

        if (!$staff) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Staff not found.',
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Staff deleted successfully.',
            'data' => [
                'id' => $staff->id,
                'deleted_at' => $staff->deleted_at,
            ]
        ], 200);
    }

}