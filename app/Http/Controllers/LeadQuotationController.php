<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Http\Requests\Quotation\UpdateQuotationRequest;
use App\Models\CompanyProfile;
use App\Models\Lead;
use App\Services\CompanyLogoService;
use App\Services\QuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The one quotation a lead may have.
 *
 * A singleton child of Lead, the same shape as LeadAssignmentController and
 * LeadCloseController: no {quotation} route parameter, because there is
 * never more than one to identify. Authorised throughout against the lead
 * itself — preparing a quotation is part of working the lead.
 */
class LeadQuotationController extends Controller
{
    public function __construct(
        private readonly QuotationService $service,
        private readonly CompanyLogoService $logos,
    ) {}

    public const DEFAULT_TERMS = "1. All Values are inclusive of all Taxes\n2. The Supply of Materials subject to the Availability\n3. We reserve the right to vary Prices in the event of changes in the price rise made by companies\n4. Material delivered after 7 days of Quotation Confirmation\n5. Shipping charges extra";

    public function create(Request $request, Lead $lead): View|RedirectResponse
    {
        $this->authorize('update', $lead);

        if ($lead->quotation) {
            return redirect()->route('leads.quotation.edit', $lead);
        }

        return view('leads.quotation.create', [
            'pageTitle' => "Quotation — {$lead->reference}",
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference, 'route' => null],
                ['label' => 'Quotation'],
            ],
            'lead' => $lead,
            'quotation' => $lead->quotation()->make([
                'customer_name' => $lead->name,
                'customer_address' => $lead->city,
                'issue_date' => today(),
                'terms' => self::DEFAULT_TERMS,
            ]),
            'items' => [['description' => '', 'quantity' => '1', 'rate' => '', 'tax_percent' => '18.00']],
        ]);
    }

    public function store(StoreQuotationRequest $request, Lead $lead): RedirectResponse
    {
        // A stale "create" tab submitted after a quotation already exists —
        // route it to update instead of raising a duplicate-key error.
        if ($lead->quotation) {
            return redirect()->route('leads.quotation.edit', $lead);
        }

        $this->service->create($lead, $request->quotationAttributes(), $request->items(), $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Quotation prepared for {$lead->reference}.");
    }

    public function edit(Request $request, Lead $lead): View|RedirectResponse
    {
        $this->authorize('update', $lead);

        $quotation = $lead->quotation;

        if (! $quotation) {
            return redirect()->route('leads.quotation.create', $lead);
        }

        $quotation->load('items');

        return view('leads.quotation.edit', [
            'pageTitle' => "Quotation — {$lead->reference}",
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference, 'route' => null],
                ['label' => 'Quotation'],
            ],
            'lead' => $lead,
            'quotation' => $quotation,
            'items' => $quotation->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'rate' => (string) $item->rate,
                'tax_percent' => (string) ($item->tax_percent ?? '18.00'),
                'basic_rate' => (string) ($item->basic_rate ?? ''),
                'tax_amount' => (string) ($item->tax_amount ?? ''),
                'amount' => (string) ($item->amount ?? ''),
            ])->all(),
        ]);
    }

    public function update(UpdateQuotationRequest $request, Lead $lead): RedirectResponse
    {
        $quotation = $lead->quotation;

        if (! $quotation) {
            return redirect()->route('leads.quotation.create', $lead);
        }

        $this->service->update($quotation, $request->quotationAttributes(), $request->items());

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Quotation updated for {$lead->reference}.");
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        if ($quotation = $lead->quotation) {
            $this->service->delete($quotation);
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Quotation removed from {$lead->reference}.");
    }

    /**
     * Download the quotation as a letterheaded PDF.
     *
     * Gated on 'view' rather than 'update': downloading a document that
     * already exists is a read, not an edit.
     */
    public function pdf(Request $request, Lead $lead): Response
    {
        $this->authorize('view', $lead);

        $quotation = $lead->quotation;

        abort_if($quotation === null, 404);

        $quotation->load('items');

        $company = CompanyProfile::current();

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'lead' => $lead,
            'company' => $company,
            'logoDataUri' => $this->logoDataUri($company),
        ])->setPaper('a4');

        return $pdf->download("Quotation-{$quotation->reference}.pdf");
    }

    /**
     * The logo inlined as a data URI. dompdf renders in an isolated process
     * with no session/auth context, so it cannot reliably fetch the logo
     * back over HTTP — embedding the bytes directly sidesteps that.
     */
    private function logoDataUri(CompanyProfile $company): ?string
    {
        $path = $this->logos->absolutePath($company->logo_path);

        if ($path === null || ! file_exists($path)) {
            $defaultLogo = public_path('assets/images/logo-dark.svg');
            if (file_exists($defaultLogo)) {
                $path = $defaultLogo;
            } else {
                return null;
            }
        }

        $mime = mime_content_type($path) ?: 'image/svg+xml';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
