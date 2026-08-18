<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfile\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use App\Services\CompanyLogoService;
use Illuminate\Http\JsonResponse;

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
}