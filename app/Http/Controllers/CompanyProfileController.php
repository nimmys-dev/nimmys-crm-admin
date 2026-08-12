<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyProfile\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use App\Services\CompanyLogoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The company letterhead used on generated documents.
 *
 * Admin-only (settings.manage), reached from the Settings page. There is
 * exactly one record — see CompanyProfile::current() — so this controller
 * has no index, create or destroy; only edit and update.
 */
class CompanyProfileController extends Controller
{
    public function __construct(private readonly CompanyLogoService $logos) {}

    public function edit(): View
    {
        $company = CompanyProfile::current();

        return view('settings.company', [
            'pageTitle' => 'Company Profile',
            'breadcrumbs' => [
                ['label' => 'Settings', 'route' => 'settings.index'],
                ['label' => 'Company Profile'],
            ],
            'company' => $company,
            'logoUrl' => $this->logos->url($company->logo_path),
        ]);
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $company = CompanyProfile::current();
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->logos->store($request->file('logo'), $company->logo_path);
        }

        $company->update($data);

        return back()->with('success', 'Company profile updated.');
    }

    /**
     * Remove just the logo, leaving the rest of the letterhead intact.
     */
    public function destroyLogo(): RedirectResponse
    {
        $company = CompanyProfile::current();

        $this->logos->delete($company->logo_path);
        $company->forceFill(['logo_path' => null])->saveQuietly();

        return back()->with('success', 'Logo removed.');
    }
}
