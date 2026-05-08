<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.delivery_note_print_title', ['reference' => $deliveryNote->reference]) }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <style>
        @page { margin: 28px 38px 118px 38px; }

        body,
        body * {
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important;
        }

        body {
            margin: 0;
            color: #233247;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td { vertical-align: top; }
        .brand-side { width: 58%; padding-top: 8px; }
        .document-side { width: 42%; text-align: right; }

        .brand-logo {
            width: 68px;
            height: 68px;
            border-radius: 9px;
            background: #eef6ff;
            color: #2c6ecb;
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .brand-logo img {
            max-width: 68px;
            max-height: 68px;
        }

        .brand-info {
            padding-left: 16px;
            vertical-align: middle;
        }

        .company-name {
            margin: 0;
            color: #172033;
            font-size: 23px;
            font-weight: bold;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .company-subtitle {
            margin-top: 3px;
            color: #485a70;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .document-title {
            margin: 0;
            color: #2f70c8;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }

        .rule-table { margin-top: 16px; margin-bottom: 34px; }
        .rule-blue { width: 84px; height: 3px; background: #40aef4; }
        .rule-grey { height: 3px; background: #a9b3bf; }

        .intro-table { margin-bottom: 26px; }
        .intro-table td { vertical-align: top; }
        .bill-to { width: 55%; }
        .delivery-meta { width: 45%; text-align: right; }

        .muted-label {
            color: #4d5f75;
            font-size: 12px;
        }

        .client-name {
            margin-top: 4px;
            color: #172033;
            font-size: 17px;
            font-weight: bold;
        }

        .client-line {
            margin-top: 6px;
            color: #6a7a8d;
            font-size: 12px;
        }

        .meta-line {
            margin-top: 5px;
            color: #172033;
            font-size: 12px;
        }

        .items { margin-top: 10px; margin-bottom: 24px; }

        .items th {
            padding: 6px 7px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .items td {
            padding: 6px 7px;
            color: #243247;
            font-size: 11px;
        }

        .items tbody tr:nth-child(odd) td { background: #ffffff; }
        .items tbody tr:nth-child(even) td { background: #c9e5f5; }
        .items .no { width: 34px; text-align: center; }
        .items .description { width: 42%; }
        .items .qty { text-align: right; white-space: nowrap; }

        .line-detail {
            display: block;
            margin-top: 2px;
            color: #58708d;
            font-size: 9.5px;
        }

        .line-serials {
            display: block;
            margin-top: 3px;
            color: #2f70c8;
            font-size: 9.5px;
        }

        .line-serials strong {
            display: block;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .line-serials-list {
            margin: 0;
            padding-left: 13px;
        }

        .line-serials-list li {
            margin: 1px 0;
        }

        .notes-signature td { vertical-align: bottom; }
        .notes { width: 55%; padding-top: 16px; color: #6a7a8d; font-size: 10px; }
        .notes strong { display: block; margin-bottom: 5px; color: #172033; font-size: 11.5px; }
        .notes-body { white-space: pre-line; }

        .delivery-qr { margin-top: 14px; }
        .delivery-qr img {
            width: 88px;
            height: 88px;
            border: 1px solid #d6e1ee;
            padding: 4px;
            background: #ffffff;
        }

        .signature { width: 45%; padding-top: 18px; color: #172033; text-align: right; }
        .signature-line {
            width: 170px;
            margin-left: auto;
            margin-right: 0;
            padding-top: 26px;
            border-bottom: 1px solid #9aa8b8;
        }
        .signature-name { margin-top: 7px; font-size: 13px; font-weight: bold; }
        .signature-role { font-size: 11px; font-weight: bold; }

        .pdf-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -92px;
            color: #172033;
            font-size: 9.5px;
            line-height: 1.25;
        }

        .pdf-footer-line { width: 100%; margin-bottom: 4px; border-collapse: collapse; }
        .pdf-footer-line .rule-blue,
        .pdf-footer-line .rule-grey { height: 3px; }
        .pdf-footer strong { font-weight: bold; }
        .pdf-footer-emphasis { color: #0b55ff; font-style: italic; }
    </style>
</head>
<body>
    @php
        $formatQuantity = fn ($value) => number_format((float) $value, 2, ',', ' ');
        $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
        $client = $deliveryNote->client;
        $signatory = $deliveryNote->creator ?: $user;
        $companyInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($company->name, 0, 2));
        $primaryAccount = $company->accounts->sortByDesc('is_primary')->first();
        $logoDataUri = null;

        if (filled($company->logo) && ! \Illuminate\Support\Str::startsWith($company->logo, ['http://', 'https://']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo);
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <main>
        <table class="header">
            <tr>
                <td class="brand-side">
                    <table>
                        <tr>
                            <td class="brand-logo">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="{{ $company->name }}">
                                @else
                                    {{ $companyInitials }}
                                @endif
                            </td>
                            <td class="brand-info">
                                <h1 class="company-name">{{ $company->name }}</h1>
                                <div class="company-subtitle">{{ $site->name }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="document-side">
                    <h2 class="document-title">{{ __('main.delivery_note_pdf_title') }}</h2>
                </td>
            </tr>
        </table>

        <table class="rule-table">
            <tr>
                <td class="rule-blue"></td>
                <td class="rule-grey"></td>
            </tr>
        </table>

        <table class="intro-table">
            <tr>
                <td class="bill-to">
                    <div class="muted-label">{{ __('main.invoice_to') }}</div>
                    <div class="client-name">{{ $client?->display_name ?? '-' }}</div>
                    @if ($client?->address)
                        <div class="client-line">{{ $client->address }}</div>
                    @endif
                </td>
                <td class="delivery-meta">
                    <div class="meta-line"><strong>{{ __('main.reference') }} :</strong> {{ $deliveryNote->reference }}</div>
                    <div class="meta-line">{{ __('main.customer_order') }} : {{ $deliveryNote->customerOrder?->reference ?? '-' }}</div>
                    <div class="meta-line">{{ __('main.delivery_date') }} : {{ $formatDate($deliveryNote->delivery_date) }}</div>
                    <div class="meta-line">{{ __('main.status') }} : <strong>{{ $statusLabels[$deliveryNote->status] ?? $deliveryNote->status }}</strong></div>
                    @if ($deliveryNote->delivered_by)
                        <div class="meta-line">{{ __('main.delivered_by') }} : {{ $deliveryNote->delivered_by }}</div>
                    @endif
                    @if ($deliveryNote->carrier)
                        <div class="meta-line">{{ __('main.carrier') }} : {{ $deliveryNote->carrier }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="no">NO</th>
                    <th class="description">{{ __('main.description') }}</th>
                    <th class="qty">{{ __('main.ordered_quantity') }}</th>
                    <th class="qty">{{ __('main.already_delivered') }}</th>
                    <th class="qty">{{ __('main.delivered_quantity') }}</th>
                    <th class="qty">{{ __('main.remaining_quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveryNote->lines as $line)
                    @php
                        $remainingAfterDelivery = max(0, (float) $line->ordered_quantity - (float) $line->already_delivered_quantity - (float) $line->quantity);
                    @endphp
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="description">
                            {{ $line->description }}
                            @if ($line->details)
                                <span class="line-detail">{{ $line->details }}</span>
                            @endif
                            @if ($line->serials->isNotEmpty())
                                <span class="line-serials">
                                    <strong>{{ __('main.serial_numbers') }} :</strong>
                                    <ul class="line-serials-list">
                                        @foreach ($line->serials as $serial)
                                            <li>{{ $serial->serial_number }}</li>
                                        @endforeach
                                    </ul>
                                </span>
                            @endif
                        </td>
                        <td class="qty">{{ $formatQuantity($line->ordered_quantity) }}</td>
                        <td class="qty">{{ $formatQuantity($line->already_delivered_quantity) }}</td>
                        <td class="qty">{{ $formatQuantity($line->quantity) }}</td>
                        <td class="qty">{{ $formatQuantity($remainingAfterDelivery) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="notes-signature">
            <tr>
                <td class="notes">
                    <strong>{{ __('main.notes') }} :</strong>
                    <div class="notes-body">{{ $deliveryNote->notes ?: '-' }}</div>
                    <div class="delivery-qr">
                        <img src="{{ $deliveryNoteQrCodeDataUri }}" alt="{{ __('main.delivery_note_qr_alt') }}">
                    </div>
                </td>
                <td class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $signatory->name }}</div>
                    @if (filled($signatory->grade))
                        <div class="signature-role">{{ $signatory->grade }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </main>

    <footer class="pdf-footer">
        <table class="pdf-footer-line">
            <tr>
                <td class="rule-blue"></td>
                <td class="rule-grey"></td>
            </tr>
        </table>
        <div><strong>{{ $company->name }}</strong></div>
        @if ($company->rccm || $company->id_nat || $company->nif)
            <div>
                @if ($company->rccm) RCCM : {{ $company->rccm }} @endif
                @if ($company->id_nat) - ID NAT : {{ $company->id_nat }} @endif
                @if ($company->nif) - NIF : {{ $company->nif }} @endif
            </div>
        @endif
        @if ($company->phone_number)
            <div>Contact : {{ $company->phone_number }}</div>
        @endif
        @if ($company->email || $company->website)
            <div>
                @if ($company->email) Email : {{ $company->email }} @endif
                @if ($company->website) - Web : {{ $company->website }} @endif
            </div>
        @endif
        @if ($company->address || $company->country)
            <div>Adresse : {{ $company->address ?: $company->country }}</div>
        @endif
        @if ($primaryAccount)
            <div>Compte : {{ $primaryAccount->account_number ?: '-' }} - {{ $primaryAccount->currency ?: '-' }} @if ($primaryAccount->bank_name) - {{ $primaryAccount->bank_name }} @endif</div>
        @endif
        <div class="pdf-footer-emphasis">{{ __('main.delivery_note_generated_by') }}</div>
    </footer>
</body>
</html>
