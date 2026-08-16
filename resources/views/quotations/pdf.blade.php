<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Quotation {{ $quotation->reference }}</title>
    <style>
        @page {
            margin: 18px 25px 25px 25px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111111;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            border-top: 3px solid #cb191d;
            padding-top: 8px;
            margin-bottom: 4px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 32%;
            padding-right: 10px;
        }

        .logo-img {
            max-width: 170px;
            max-height: 60px;
        }

        .logo-fallback {
            font-size: 26px;
            font-weight: 900;
            color: #cb191d;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .logo-fallback-sub {
            font-size: 10px;
            font-weight: bold;
            color: #111;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .branch-cell {
            width: 17%;
            padding: 0 4px;
            font-size: 7.5px;
            line-height: 1.25;
            border-left: 2px solid #cb191d;
            padding-left: 5px;
        }

        .branch-title {
            font-weight: bold;
            font-size: 8.5px;
            color: #111;
            margin-bottom: 2px;
        }

        .branch-phone {
            margin-top: 2px;
            font-weight: 500;
        }

        .gst-banner {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            font-size: 11.5px;
            text-align: center;
            padding: 4px 0;
            margin-top: 6px;
            letter-spacing: 0.8px;
        }

        .quotation-title-banner {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 1.5px;
            padding: 4px 0;
            border-bottom: 1px solid #111111;
        }

        .meta-table {
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .meta-table td {
            vertical-align: top;
        }

        .recipient-box {
            font-size: 10px;
            line-height: 1.35;
        }

        .recipient-to {
            font-size: 9px;
            margin-bottom: 2px;
        }

        .recipient-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
        }

        .recipient-address {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
            line-height: 1.35;
        }

        .doc-info-table {
            width: auto;
            float: right;
            font-size: 10px;
            font-weight: bold;
        }

        .doc-info-table td {
            padding: 1px 0;
        }

        .doc-info-label {
            padding-right: 12px;
            text-align: left;
        }

        .doc-info-val {
            text-align: left;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            border: 1px solid #000000;
        }

        .items-table th {
            border: 1px solid #000000;
            font-size: 9px;
            font-weight: bold;
            padding: 5px 3px;
            text-align: center;
            background-color: #ffffff;
            vertical-align: middle;
        }

        .items-table td {
            border: 1px solid #000000;
            font-size: 9.5px;
            padding: 5px 4px;
            vertical-align: middle;
        }

        .items-table .num-cell {
            text-align: right;
            padding-right: 5px;
            font-variant-numeric: tabular-nums;
        }

        .items-table .center-cell {
            text-align: center;
        }

        .grand-total-row td {
            border: 1px solid #000000;
            font-weight: bold;
            padding: 6px 5px;
            font-size: 10.5px;
        }

        /* Footer sections */
        .bottom-section {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .bottom-section td {
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
            font-size: 9.5px;
            text-decoration: underline;
            margin-bottom: 4px;
        }

        .terms-list {
            font-size: 8.5px;
            line-height: 1.35;
            margin-bottom: 10px;
            white-space: pre-line;
        }

        .bank-details-table {
            width: 100%;
            font-size: 8.5px;
            line-height: 1.3;
        }

        .bank-details-table td {
            padding: 1px 0;
        }

        .signatory-box {
            text-align: center;
            padding-top: 15px;
        }

        .signatory-company {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .signatory-sd {
            font-size: 9px;
            margin-bottom: 4px;
            color: #333;
        }

        .signatory-line {
            font-size: 9.5px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 2px;
            display: inline-block;
            min-width: 140px;
        }

        .bottom-banner-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
        }

        .bottom-banner {
            background-color: #cb191d;
            color: #ffffff;
            font-weight: bold;
            font-size: 10.5px;
            text-align: center;
            padding: 6px 0;
            border-radius: 40px 40px 0 0;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>

    {{-- Top Header with Logo & 4 Branches --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if ($logoDataUri)
                    <img class="logo-img" src="{{ $logoDataUri }}" alt="Nimmys Camera Centre" />
                @else
                    <div class="logo-fallback">nimmys</div>
                    <div class="logo-fallback-sub">&#9632; camera centre</div>
                @endif
            </td>

            <td class="branch-cell">
                <div class="branch-title">Ettumanoor</div>
                Nimmys Tower<br />
                MC Road<br />
                Kottayam Dist<br />
                Kerala-686631<br />
                <div class="branch-phone">Ph: +91 9961203700</div>
            </td>

            <td class="branch-cell">
                <div class="branch-title">Kottayam</div>
                Kalambukattu Buildings<br />
                Opp. Kalyan silks<br />
                T.B Road<br />
                Kottayam-686001<br />
                <div class="branch-phone">Ph: +91 9961204700</div>
            </td>

            <td class="branch-cell">
                <div class="branch-title">Kanjirappally</div>
                Loyola building<br />
                Thampalakkadu Road<br />
                Kottayam Dist<br />
                Kerala-686507<br />
                <div class="branch-phone">Ph: +91 9961216700</div>
            </td>

            <td class="branch-cell">
                <div class="branch-title">Vyttila</div>
                Nimmys camera centre<br />
                Tharayil annexe<br />
                NH Bypass Road, Vyttila P.O<br />
                Cochin-682019<br />
                <div class="branch-phone">Ph: +91 9961205700</div>
            </td>
        </tr>
    </table>

    {{-- GST Banner --}}
    <div class="gst-banner">
        GST: 32BKEPS5436H1Z4
    </div>

    {{-- Quotation Title Banner --}}
    <div class="quotation-title-banner">
        QUOTATION
    </div>

    {{-- Customer & Quotation Metadata --}}
    <table class="meta-table">
        <tr>
            <td style="width: 60%;">
                <div class="recipient-box">
                    <div class="recipient-to">To</div>
                    <div class="recipient-name">{{ $quotation->customer_name }}</div>
                    @if ($quotation->customer_address)
                        <div class="recipient-address">{!! nl2br(e($quotation->customer_address)) !!}</div>
                    @endif
                </div>
            </td>

            <td style="width: 40%; text-align: right;">
                <table class="doc-info-table">
                    <tr>
                        <td class="doc-info-label">No:</td>
                        <td class="doc-info-val">{{ $quotation->reference }}</td>
                    </tr>
                    <tr>
                        <td class="doc-info-label">Date :</td>
                        <td class="doc-info-val">{{ $quotation->issue_date->format('d.m.Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">SI .No.</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 14%;">Rate inclusive<br />of tax</th>
                <th style="width: 13%;">Basic Rate</th>
                <th style="width: 7%;">Tax %</th>
                <th style="width: 9%;">Tax Amt</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                @php
                    $qty = (float) $item->quantity;
                    $rate = (float) $item->rate;
                    $taxPercent = (float) ($item->tax_percent ?? 18.0);
                    $basicRate = (float) ($item->basic_rate ?: ($taxPercent > 0 ? ($rate / (1 + ($taxPercent / 100))) : $rate));
                    $taxAmt = (float) ($item->tax_amount ?: (($rate - $basicRate) * $qty));
                    $total = (float) $item->amount;
                @endphp
                <tr>
                    <td class="center-cell">{{ $loop->iteration }}</td>
                    <td style="text-align: left; padding-left: 6px;">{{ $item->description }}</td>
                    <td class="center-cell">{{ number_format($qty, 0) == $qty ? number_format($qty, 0) : number_format($qty, 2) }}</td>
                    <td class="num-cell">{{ number_format($rate, 2) }}</td>
                    <td class="num-cell">{{ number_format($basicRate, 2) }}</td>
                    <td class="center-cell">{{ number_format($taxPercent, 2) }}</td>
                    <td class="num-cell">{{ number_format($taxAmt, 2) }}</td>
                    <td class="num-cell">{{ number_format($total, 2) }}</td>
                </tr>
            @endforeach

            <tr class="grand-total-row">
                <td colspan="7" class="center-cell" style="padding: 7px 10px;">
                    {{ $quotation->amountInWords() }}
                </td>
                <td class="num-cell" style="font-size: 11px;">
                    {{ number_format((float) $quotation->total, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Terms & Conditions + Bank Details + Signatory --}}
    <table class="bottom-section">
        <tr>
            <td style="width: 62%;">
                @if ($quotation->terms)
                    <div class="section-title">Terms and Conditions</div>
                    <div class="terms-list">{!! nl2br(e($quotation->terms)) !!}</div>
                @endif

                <div class="section-title" style="margin-top: 8px;">For NEFT/RTGS-Account Details</div>
                <table class="bank-details-table">
                    <tr>
                        <td style="width: 80px;">A/c Number:-</td>
                        <td style="font-weight: bold;">99995000080000</td>
                    </tr>
                    <tr>
                        <td>A/c Name:-</td>
                        <td style="font-weight: bold;">NIMMYS CAMERA CENTRE</td>
                    </tr>
                    <tr>
                        <td>Bank-</td>
                        <td style="font-weight: bold;">HDFC BANK</td>
                    </tr>
                    <tr>
                        <td>Branch-</td>
                        <td style="font-weight: bold;">ETTUMANOOR</td>
                    </tr>
                    <tr>
                        <td>IFSC-</td>
                        <td style="font-weight: bold;">HDFC0001503</td>
                    </tr>
                </table>
            </td>

            <td style="width: 38%; padding-left: 20px;">
                <div class="signatory-box">
                    <div class="signatory-company">For Nimmys Camera Centre</div>
                    <div class="signatory-sd">Sd/-</div>
                    <div class="signatory-line">Authorized Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Bottom Online Store Banner --}}
    <div class="bottom-banner-container">
        <div class="bottom-banner">
            Our Online Store: www.nimmysonline.com
        </div>
    </div>

</body>
</html>
