<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Quotation {{ $quotation->reference }}</title>
    <style>
        /*
         * dompdf renders this in isolation from the app's own stylesheet, so
         * everything the document needs is inlined here. Table-based layout
         * throughout — dompdf's flexbox/grid support is too inconsistent to
         * rely on for a document that has to look right unattended.
         */
        @page {
            margin: 34px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #3e4853;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .muted {
            color: #8996a4;
        }

        .text-right {
            text-align: right;
        }

        /* ---------------------------------------------------- Letterhead */

        .letterhead td {
            vertical-align: top;
        }

        .letterhead .logo {
            max-width: 150px;
            max-height: 64px;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #7a0000;
        }

        .company-details {
            margin-top: 2px;
            line-height: 1.5;
            color: #8996a4;
        }

        .doc-title {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #7a0000;
            text-transform: uppercase;
        }

        .doc-meta {
            margin-top: 8px;
            line-height: 1.6;
        }

        .doc-meta .label {
            color: #8996a4;
            padding-right: 10px;
        }

        .rule {
            border-top: 2px solid #7a0000;
            margin: 14px 0 16px;
        }

        /* --------------------------------------------------------- Bill to */

        .bill-to .label {
            font-size: 9px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #8996a4;
        }

        .bill-to .name {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
        }

        .bill-to .address {
            margin-top: 3px;
            line-height: 1.5;
            white-space: pre-line;
        }

        /* -------------------------------------------------------- Items */

        .items {
            margin-top: 22px;
        }

        .items th {
            background-color: #7a0000;
            color: #ffffff;
            font-size: 9.5px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-align: left;
            padding: 8px 10px;
        }

        .items th.text-right {
            text-align: right;
        }

        .items td {
            padding: 8px 10px;
            border-bottom: 1px solid #e8ebee;
        }

        .items tr:nth-child(even) td {
            background-color: #f7f8f9;
        }

        .items .col-no {
            width: 26px;
            color: #8996a4;
        }

        .items .col-qty,
        .items .col-rate,
        .items .col-amount {
            width: 70px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* -------------------------------------------------------- Totals */

        .totals {
            margin-top: 4px;
        }

        .totals table {
            width: 240px;
        }

        .totals td {
            padding: 5px 10px;
        }

        .totals .label {
            color: #8996a4;
        }

        .totals .value {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .totals .grand td {
            border-top: 2px solid #7a0000;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        /* --------------------------------------------------------- Terms */

        .terms {
            margin-top: 26px;
            padding-top: 12px;
            border-top: 1px solid #e8ebee;
        }

        .terms .label {
            font-size: 9px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #8996a4;
            margin-bottom: 4px;
        }

        .terms .body {
            white-space: pre-line;
            line-height: 1.5;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e8ebee;
            color: #8996a4;
            font-size: 9.5px;
            text-align: center;
        }
    </style>
</head>
<body>

    <table class="letterhead">
        <tr>
            <td style="width: 55%;">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="{{ $company->name }}" />
                @endif

                <div class="company-name">{{ $company->name }}</div>

                <div class="company-details">
                    @if ($company->fullAddress())
                        {{ $company->fullAddress() }}<br />
                    @endif
                    @if ($company->phone)
                        Phone: {{ $company->phone }}<br />
                    @endif
                    @if ($company->email)
                        Email: {{ $company->email }}
                    @endif
                </div>
            </td>

            <td style="width: 45%;" class="text-right">
                <div class="doc-title">Quotation</div>

                <div class="doc-meta">
                    <span class="label">Reference</span>{{ $quotation->reference }}<br />
                    <span class="label">Date</span>{{ $quotation->issue_date->format('d M Y') }}<br />
                    @if ($quotation->valid_until)
                        <span class="label">Valid until</span>{{ $quotation->valid_until->format('d M Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="bill-to">
        <tr>
            <td>
                <div class="label">Quotation for</div>
                <div class="name">{{ $quotation->customer_name }}</div>
                @if ($quotation->customer_address)
                    <div class="address">{{ $quotation->customer_address }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="col-no">#</th>
                <th>Item / Product</th>
                <th class="text-right col-qty">Qty</th>
                <th class="text-right col-rate">Rate</th>
                <th class="text-right col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right col-qty">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="text-right col-rate">{{ number_format((float) $item->rate, 2) }}</td>
                    <td class="text-right col-amount">{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%;">
        <tr>
            <td></td>
            <td style="width: 240px;">
                <table class="totals">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">{{ number_format((float) $quotation->subtotal, 2) }}</td>
                    </tr>

                    @if ($quotation->discount_percent)
                        <tr>
                            <td class="label">Discount ({{ rtrim(rtrim(number_format((float) $quotation->discount_percent, 2), '0'), '.') }}%)</td>
                            <td class="value">&minus;{{ number_format($quotation->discountAmount(), 2) }}</td>
                        </tr>
                    @endif

                    @if ($quotation->tax_percent)
                        <tr>
                            <td class="label">Tax ({{ rtrim(rtrim(number_format((float) $quotation->tax_percent, 2), '0'), '.') }}%)</td>
                            <td class="value">{{ number_format($quotation->taxAmount(), 2) }}</td>
                        </tr>
                    @endif

                    <tr class="grand">
                        <td>Total</td>
                        <td class="value">{{ number_format((float) $quotation->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($quotation->terms)
        <div class="terms">
            <div class="label">Terms &amp; Notes</div>
            <div class="body">{{ $quotation->terms }}</div>
        </div>
    @endif

    <div class="footer">
        {{ $company->name }} &middot; Quotation {{ $quotation->reference }} &middot; Generated {{ now()->format('d M Y') }}
    </div>

</body>
</html>
