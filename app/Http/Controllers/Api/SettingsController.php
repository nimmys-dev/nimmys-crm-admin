<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfile\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use App\Services\CompanyLogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function __construct(
        protected CompanyLogoService $logos
    ) {
    }

    /**
     * Get company profile
     */
    public function companyProfile(): JsonResponse
    {
        $company = CompanyProfile::current();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Company profile retrieved successfully',

            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'quotation_prefix' => $company->quotation_prefix ?? 'QTN',
                'address_line' => $company->address_line,
                'city' => $company->city,
                'state' => $company->state,
                'postal_code' => $company->postal_code,
                'country' => $company->country,
                'phone' => $company->phone,
                'email' => $company->email,
                'logo' => $company->logo_path
                ? url('storage/' . $company->logo_path)
                : null,
            ],
        ], 200);
    }


    /**
     * Update company profile
     */
    public function updateCompanyProfile(
        UpdateCompanyProfileRequest $request
        ): JsonResponse {

        $company = CompanyProfile::current();

        $data = $request->validated();

        if ($request->hasFile('logo')) {

            $data['logo_path'] = $this->logos->store(
                $request->file('logo'),
                $company->logo_path
            );

            unset($data['logo']);
        }

        $company->update($data);

        $company->refresh();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Company profile updated successfully',

            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'quotation_prefix' => $company->quotation_prefix ?? 'QTN',
                'address_line' => $company->address_line,
                'city' => $company->city,
                'state' => $company->state,
                'postal_code' => $company->postal_code,
                'country' => $company->country,
                'phone' => $company->phone,
                'email' => $company->email,

                // Full HTTP URL
                'logo' => $company->logo_path
                    ? url('storage/' . $company->logo_path)
                    : null,
            ],
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Password changed successfully.',
        ], 200);
    }
}