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
        $perPage = $request->get('per_page', 10);

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
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('employee_code', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->shop_id, function ($query) use ($request) {
                $query->where('shop_id', $request->shop_id);
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage);

        $staff->getCollection()->transform(function ($item) {
            $item->photo_url = $item->photo
                ? url(\Illuminate\Support\Facades\Storage::url($item->photo))
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
        ]);
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

}