<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use App\Support\NumberToWords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    #[Test]
    public function an_admin_can_create_a_quotation_with_tax_and_basic_rate_fields(): void
    {
        $lead = Lead::factory()->create(['name' => 'Visakh V Vinod', 'city' => 'Kottayam']);

        $payload = [
            'customer_name' => 'VISAKH V VINOD',
            'customer_address' => "VATTAKKAD\nMANTHURUTHY P. O\nNEDUMKUNNAM, KOTTAYAM -686542",
            'issue_date' => '2026-08-13',
            'items' => [
                [
                    'description' => 'Alpha ILCE 7RM5 B Sony',
                    'quantity' => 1,
                    'rate' => 278000.00,
                    'tax_percent' => 18.00,
                ],
                [
                    'description' => 'Lens 50mm 1.4 GM Sony',
                    'quantity' => 1,
                    'rate' => 119000.00,
                    'tax_percent' => 18.00,
                ],
            ],
            'terms' => "1. All Values are inclusive of all Taxes\n2. The Supply of Materials subject to the Availability",
        ];

        $this->actingAs($this->admin())
            ->post(route('leads.quotation.store', $lead), $payload)
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $quotation = Quotation::where('lead_id', $lead->id)->with('items')->first();

        $this->assertNotNull($quotation);
        $this->assertSame('397000.00', (string) $quotation->total);
        $this->assertCount(2, $quotation->items);

        $firstItem = $quotation->items->first();
        $this->assertSame('Alpha ILCE 7RM5 B Sony', $firstItem->description);
        $this->assertSame('278000.00', (string) $firstItem->rate);
        $this->assertSame('18.00', (string) $firstItem->tax_percent);
        $this->assertSame('235593.22', (string) $firstItem->basic_rate);
        $this->assertSame('42406.78', (string) $firstItem->tax_amount);
        $this->assertSame('278000.00', (string) $firstItem->amount);

        $this->assertSame('Three Lakh Ninety Seven Thousand Only', $quotation->amountInWords());
    }

    #[Test]
    public function indian_currency_words_converts_correctly(): void
    {
        $this->assertSame('Four Lakh Fifty Thousand Four Hundred Only', NumberToWords::indianCurrency(450400));
        $this->assertSame('Two Lakh Seventy Eight Thousand Only', NumberToWords::indianCurrency(278000));
        $this->assertSame('One Crore Only', NumberToWords::indianCurrency(10000000));
        $this->assertSame('Zero Only', NumberToWords::indianCurrency(0));
    }

    #[Test]
    public function quotation_pdf_can_be_downloaded(): void
    {
        $lead = Lead::factory()->create(['name' => 'Visakh V Vinod']);
        $quotation = Quotation::factory()->create([
            'lead_id' => $lead->id,
            'customer_name' => 'VISAKH V VINOD',
            'customer_address' => 'Kottayam',
            'total' => 450400.00,
            'subtotal' => 450400.00,
        ]);

        $quotation->items()->create([
            'description' => 'Alpha ILCE 7RM5 B Sony',
            'quantity' => 1,
            'rate' => 278000.00,
            'tax_percent' => 18.00,
            'basic_rate' => 235593.22,
            'tax_amount' => 42406.78,
            'amount' => 278000.00,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('leads.quotation.pdf', $lead));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
