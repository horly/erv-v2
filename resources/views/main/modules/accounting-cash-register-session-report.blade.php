<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.cash_register_closing_report') }} {{ $session->reference }}</title>
    @unless (($isPdf ?? false) === true)
        <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    @endunless
    <style>
        @page { margin: 28px 38px 118px 38px; }
        * { box-sizing: border-box; }
        body,
        body * {
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important;
        }
        body {
            margin: 0;
            color: #233247;
            font-size: 12px;
            line-height: 1.35;
            background: {{ ($isPdf ?? false) === true ? '#ffffff' : '#f4f7fb' }};
        }
        table { width: 100%; border-collapse: collapse; }
        .report-page {
            width: 100%;
            max-width: 1040px;
            margin: {{ ($isPdf ?? false) === true ? '0 auto' : '24px auto' }};
            padding: {{ ($isPdf ?? false) === true ? '0' : '30px' }};
            border: {{ ($isPdf ?? false) === true ? '0' : '1px solid #d9e3f2' }};
            border-radius: {{ ($isPdf ?? false) === true ? '0' : '14px' }};
            background: #fff;
        }
        .report-header-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .report-header-table td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }
        .company-name {
            margin: 0;
            color: #172033;
            font-size: 23px;
            font-weight: 900;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .company-meta {
            margin-top: 2px;
            color: #485a70;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .08em;
            line-height: 1.35;
            text-transform: uppercase;
        }
        .report-title-cell {
            width: 44%;
            text-align: right;
        }
        .report-title {
            margin: 0;
            color: #2f70c8;
            font-size: 36px;
            font-weight: bold;
            line-height: 1;
            letter-spacing: .08em;
            text-align: right;
            text-transform: uppercase;
        }
        .report-reference {
            margin-top: 18px;
            text-align: right;
            line-height: 1.35;
        }
        .report-reference strong {
            font-size: 13px;
        }
        .rule-table {
            margin-top: 16px;
            margin-bottom: 36px;
            border-collapse: collapse;
        }
        .rule-blue {
            width: 84px;
            height: 3px;
            padding: 0;
            background: #40aef4;
        }
        .rule-grey {
            height: 3px;
            padding: 0;
            background: #a9b3bf;
        }
        .section-title {
            margin: 0 0 24px;
            color: #172033;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .muted { color: #64748b; }
        .section {
            margin-bottom: 34px;
        }
        .session-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .session-table td {
            padding: 10px 14px;
            border: 0;
            line-height: 1.3;
        }
        .session-table tr:nth-child(2) {
            background: #d8eef8;
        }
        .session-table .label-cell {
            width: 26%;
        }
        .session-table .value-cell {
            width: 18%;
        }
        .session-table .label-cell-wide {
            width: 20%;
        }
        .session-table .value-cell-wide {
            width: 36%;
        }
        .metrics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin: 0 -12px;
        }
        .metrics-table td {
            width: 25%;
            padding: 0;
            border: 0;
        }
        .metric {
            min-height: 78px;
            border: 1px solid #d9e3f2;
            border-radius: 10px;
            padding: 14px 16px 12px;
            background: #f8fbff;
        }
        .metric strong {
            display: block;
            margin-top: 7px;
            color: #172033;
            font-size: 17px;
            line-height: 1.1;
        }
        .data-table th {
            background: #2f70c8;
            color: #fff;
            font-size: 11px;
            text-align: left;
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: .02em;
        }
        .data-table th,
        .data-table td {
            padding: 8px 14px;
            border: 0;
            line-height: 1.3;
        }
        .data-table tbody tr:nth-child(even) { background: #d8eef8; }
        .ticket-table thead th:first-child {
            border-top-left-radius: 8px;
        }
        .ticket-table thead th:last-child {
            border-top-right-radius: 8px;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .status {
            display: inline-block;
            border-radius: 999px;
            padding: 5px 10px;
            color: #047857;
            background: #d1fae5;
            font-size: 10.5px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .pdf-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -92px;
            color: #172033;
            font-size: 9.5px;
            line-height: 1.25;
        }
        .pdf-footer-line {
            width: 100%;
            margin-bottom: 4px;
            border-collapse: collapse;
        }
        .pdf-footer-emphasis {
            color: #0b55ff;
            font-style: italic;
        }
        .report-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin: 0 auto 16px;
            max-width: 980px;
        }
        .report-actions a,
        .report-actions button {
            border: 1px solid #d9e3f2;
            border-radius: 10px;
            background: #fff;
            color: #2563eb;
            font: inherit;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }
        @media print {
            body { background: #fff; }
            .report-page { margin: 0; border: none; border-radius: 0; }
            .report-actions { display: none; }
        }
    </style>
</head>
<body>
    @unless (($isPdf ?? false) === true)
        <div class="report-actions">
            <a href="{{ route('main.accounting.cash-register', [$company, $site]) }}">{{ __('main.back') }}</a>
            <button type="button" onclick="window.print()">{{ __('main.print_pdf') }}</button>
        </div>
    @endunless

    @php
        $primaryAccount = $company->accounts->sortByDesc('is_primary')->first();
    @endphp

    <main class="report-page">
        <table class="report-header-table">
            <tr>
                <td>
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-meta">
                        {{ $site->name }}<br>
                        {{ $site->email ?: $company->email }}
                    </div>
                </td>
                <td class="report-title-cell">
                    <h1 class="report-title">{{ __('main.cash_register_closing_report') }}</h1>
                    <div class="report-reference">
                        <strong>{{ $session->reference }}</strong><br>
                        {{ __('main.cash_register_status_closed') }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="rule-table"><tr><td class="rule-blue"></td><td class="rule-grey"></td></tr></table>

        <section class="section">
            <h2 class="section-title">{{ __('main.cash_register_session') }}</h2>
            <table class="session-table">
                <tbody>
                    <tr>
                        <td class="label-cell">{{ __('main.opened_by') }}</td>
                        <td class="value-cell">{{ $session->opener?->name ?? '-' }}</td>
                        <td class="label-cell-wide">{{ __('main.opened_at') }}</td>
                        <td class="value-cell-wide">{{ optional($session->opened_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">{{ __('main.closed_by') }}</td>
                        <td class="value-cell">{{ $session->closer?->name ?? '-' }}</td>
                        <td class="label-cell-wide">{{ __('main.closed_at') }}</td>
                        <td class="value-cell-wide">{{ optional($session->closed_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">{{ __('main.validated_by') }}</td>
                        <td class="value-cell">{{ $session->validator?->name ?? '-' }}</td>
                        <td class="label-cell-wide">{{ __('main.cash_register_sales_count') }}</td>
                        <td class="value-cell-wide">{{ $session->salesInvoices->count() }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">{{ __('main.cash_register_closing_amounts') }}</h2>
            <table class="metrics-table">
                <tr>
                    <td><div class="metric">{{ __('main.opening_float') }}<strong>{{ number_format((float) $session->opening_float, 2, ',', ' ') }} {{ $site->currency }}</strong></div></td>
                    <td><div class="metric">{{ __('main.expected_total_amount') }}<strong>{{ number_format((float) $session->expected_total_amount, 2, ',', ' ') }} {{ $site->currency }}</strong></div></td>
                    <td><div class="metric">{{ __('main.counted_total_amount') }}<strong>{{ number_format((float) $session->counted_total_amount, 2, ',', ' ') }} {{ $site->currency }}</strong></div></td>
                    <td><div class="metric">{{ __('main.difference_amount') }}<strong>{{ number_format((float) $session->difference_amount, 2, ',', ' ') }} {{ $site->currency }}</strong></div></td>
                </tr>
            </table>
        </section>

        <section class="section">
        <h2 class="section-title">{{ __('main.payment_method') }}</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('main.payment_method') }}</th>
                    <th>{{ __('main.payment_method_type') }}</th>
                    <th class="text-end">{{ __('main.payments_count') }}</th>
                    <th class="text-end">{{ __('main.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paymentSummary as $summary)
                    <tr>
                        <td>{{ $summary->paymentMethod?->name ?? '-' }}</td>
                        <td>{{ $summary->paymentMethod?->type ?? '-' }}</td>
                        <td class="text-end">{{ $summary->payments_count }}</td>
                        <td class="text-end">{{ number_format((float) $summary->total_amount, 2, ',', ' ') }} {{ $summary->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">{{ __('main.no_cash_register_tickets') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </section>

        <section class="section">
        <h2 class="section-title">{{ __('main.cash_register_tickets') }}</h2>
        <table class="data-table ticket-table">
            <thead>
                <tr>
                    <th>{{ __('main.reference') }}</th>
                    <th>{{ __('main.customer') }}</th>
                    <th>{{ __('main.sale_date') }}</th>
                    <th class="text-end">{{ __('main.total_ttc') }}</th>
                    <th>{{ __('main.invoice_status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($session->salesInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->reference }}</td>
                        <td>{{ $invoice->client?->display_name ?? __('main.walk_in_customer') }}</td>
                        <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                        <td><span class="status">{{ $invoiceStatusLabels[$invoice->status] ?? $invoice->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">{{ __('main.no_cash_register_tickets') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </section>
    </main>

    <footer class="pdf-footer">
        <table class="pdf-footer-line"><tr><td class="rule-blue"></td><td class="rule-grey"></td></tr></table>
        <div><strong>{{ $company->name }}</strong></div>
        @if ($company->rccm || $company->id_nat || $company->nif)
            <div>
                @if ($company->rccm) RCCM : {{ $company->rccm }} @endif
                @if ($company->id_nat) - ID NAT : {{ $company->id_nat }} @endif
                @if ($company->nif) - NIF : {{ $company->nif }} @endif
            </div>
        @endif
        @if ($company->phone_number)<div>Contact : {{ $company->phone_number }}</div>@endif
        @if ($company->email || $company->website)
            <div>
                @if ($company->email) Email : {{ $company->email }} @endif
                @if ($company->website) - Web : {{ $company->website }} @endif
            </div>
        @endif
        @if ($company->address || $company->country)<div>Adresse : {{ $company->address ?: $company->country }}</div>@endif
        @if ($primaryAccount)
            <div>Compte : {{ $primaryAccount->account_number ?: '-' }} - {{ $primaryAccount->currency ?: '-' }} @if ($primaryAccount->bank_name) - {{ $primaryAccount->bank_name }} @endif</div>
        @endif
        <div class="pdf-footer-emphasis">{{ __('main.invoice_generated_by') }}</div>
    </footer>
</body>
</html>
