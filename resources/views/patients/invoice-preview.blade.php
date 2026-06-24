<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anteprima fattura {{ $invoice->number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f6faf9] text-ink">
        @php
            $statusLabels = [
                'draft' => 'Bozza',
                'sent' => 'Emessa',
                'paid' => 'Pagata',
                'cancelled' => 'Annullata',
            ];
            $paymentLabel = $invoice->payment_method && isset($paymentMethods[$invoice->payment_method])
                ? \Illuminate\Support\Str::after($paymentMethods[$invoice->payment_method], ' - ')
                : 'Non indicato';
            $logo = asset('images/logo-filipponi.png');
            $signaturePath = storage_path('app/private/firma-filipponi-danilo.png');
            $signature = is_file($signaturePath)
                ? 'data:image/png;base64,'.base64_encode(file_get_contents($signaturePath))
                : null;
            $patientAddress = trim(collect([$patient->address, $patient->street_number])->filter()->join(' '));
            $patientCity = collect([$patient->postal_code, $patient->city, $patient->province])->filter()->join(' ');
        @endphp
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-5 print:hidden">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anteprima fattura {{ $invoice->number }}</h2>
                <p class="mt-1 text-sm text-gray-500">A schermo vedi una copia. Per stampare usa il PDF pulito, senza menu del gestionale.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" id="open-invoice-pdf-modal" data-invoice-pdf-url="{{ route('patients.invoices.pdf', [$patient, $invoice]) }}#toolbar=0&navpanes=0&zoom=page-width" class="rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">Stampa</button>
                <a href="{{ route('patients.invoices.index', ['patient' => $patient, 'open_invoice' => $invoice->id]) }}#invoice-{{ $invoice->id }}" class="rounded-xl border border-sage bg-white px-4 py-2.5 text-sm font-bold text-sage shadow-sm hover:bg-mist">Modifica fattura</a>
                <a href="{{ route('patients.invoices.index', $patient) }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Torna alle fatture</a>
            </div>
        </div>

    @php
        $invoiceCopy = function (string $copyLabel) use ($invoice, $patient, $settings, $amounts, $paymentLabel, $statusLabels, $logo, $signature, $patientAddress, $patientCity) {
    @endphp
        <article class="invoice-copy">
            <header class="invoice-head">
                <img src="{{ $logo }}" alt="Danilo Filipponi Riabilitazione Osteopatia" class="invoice-logo">
                <div class="invoice-meta">
                    <p class="invoice-copy-label">{{ $copyLabel }}</p>
                    <p class="invoice-title">Fattura</p>
                    <p class="invoice-number">{{ $invoice->number }}</p>
                    <p class="invoice-date">{{ $invoice->issued_at->format('d/m/Y') }}</p>
                </div>
            </header>

            <section class="invoice-parties">
                <div>
                    <p class="invoice-section-title">Cedente / prestatore</p>
                    <p class="invoice-name">{{ $settings['invoice_sender_name'] }}</p>
                    <p>{{ $settings['invoice_sender_address'] }}</p>
                    <p>{{ $settings['invoice_sender_postal_code'] }} {{ $settings['invoice_sender_city'] }} {{ $settings['invoice_sender_province'] }}</p>
                    <p>P.IVA {{ $settings['invoice_sender_vat_number'] }}</p>
                    <p>CF {{ $settings['invoice_sender_tax_code'] }}</p>
                </div>
                <div>
                    <p class="invoice-section-title">Cliente</p>
                    <p class="invoice-name">{{ $patient->full_name }}</p>
                    <p>{{ $patientAddress ?: 'Indirizzo non inserito' }}</p>
                    <p>{{ $patientCity ?: 'Comune non inserito' }}</p>
                    <p>CF {{ $patient->fiscal_code ?: 'Non inserito' }}</p>
                </div>
            </section>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Descrizione</th>
                        <th>Quantita</th>
                        <th>Importo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $invoice->service }}</strong>
                            <span>Quantita: {{ number_format($amounts['quantity'], 0, ',', '.') }} - Prezzo unitario € {{ number_format($amounts['unit'], 2, ',', '.') }}</span>
                        </td>
                        <td>{{ number_format($amounts['quantity'], 0, ',', '.') }}</td>
                        <td>€ {{ number_format($amounts['line'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <section class="invoice-bottom">
                <div class="invoice-payment">
                    <p><strong>Pagamento:</strong> {{ $paymentLabel }}</p>
                    <p><strong>Data pagamento:</strong> {{ ($invoice->payment_date ?: $invoice->issued_at)->format('d/m/Y') }}</p>
                    <p><strong>Stato:</strong> {{ $statusLabels[$invoice->status] ?? $invoice->status }}</p>
                </div>
                <div class="invoice-totals">
                    <p><span>I.N.P.S. {{ number_format($amounts['social_security_rate'], 0, ',', '.') }}%</span><strong>€ {{ number_format($amounts['social_security'], 2, ',', '.') }}</strong></p>
                    <p><span>IVA {{ number_format($amounts['vat_rate'], 2, ',', '.') }}% {{ $amounts['vat_nature'] }}</span><strong>€ {{ number_format($amounts['vat'], 2, ',', '.') }}</strong></p>
                    <p><span>Bollo</span><strong>€ {{ number_format($amounts['stamp'], 2, ',', '.') }}</strong></p>
                    <p class="invoice-total"><span>Totale</span><strong>EUR {{ number_format($amounts['total'], 2, ',', '.') }}</strong></p>
                </div>
            </section>

            <footer class="invoice-footer">
                <p>Regime fiscale forfettario ex art.1, commi 54 e segg., della Legge n. 190/2014 così come modificato dalla Legge n. 208/2015 e dalla Legge n. 145/2018.</p>
                <p>Operazione in franchigia da IVA-non soggetta a ritenuta d'acconto.</p>
                <p class="invoice-courtesy">COPIA DI CORTESIA</p>
                @if ($signature)
                    <div class="invoice-signature">
                        <p>Firma</p>
                        <img src="{{ $signature }}" alt="Firma Danilo Filipponi">
                    </div>
                @endif
            </footer>
        </article>
    @php
        };
    @endphp

    <div class="px-4 py-6 print:py-0">
        <div class="mx-auto w-full max-w-[1320px]">
            <div class="screen-preview">
                @php $invoiceCopy('Copia cliente'); @endphp
            </div>

            <div class="print-sheet">
                @php $invoiceCopy('Copia cliente'); @endphp
                <div class="cut-line" aria-hidden="true"></div>
                @php $invoiceCopy('Copia studio'); @endphp
            </div>
        </div>
    </div>

    <div id="invoice-pdf-modal" class="fixed inset-0 z-50 hidden bg-slate-950/55 p-1 backdrop-blur-sm sm:p-2" aria-hidden="true">
        <div class="mx-auto flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl" style="height: calc(100vh - 16px); width: calc(100vw - 16px); max-width: none;">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-muted">Anteprima fattura</p>
                    <h3 class="text-lg font-bold text-ink">PDF stampabile</h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('patients.invoices.index', ['patient' => $patient, 'open_invoice' => $invoice->id]) }}#invoice-{{ $invoice->id }}" class="inline-flex items-center justify-center rounded-xl border border-sage bg-white px-4 py-2 text-sm font-bold text-sage hover:bg-mist">Modifica fattura</a>
                    <button type="button" id="invoice-pdf-close" class="inline-flex items-center justify-center rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white hover:bg-[#4f7f75]">Chiudi</button>
                </div>
            </div>
            <iframe id="invoice-pdf-frame" src="about:blank" class="block min-h-0 flex-1 border-0 bg-white" style="height: calc(100vh - 104px); width: 100%;" title="Anteprima PDF fattura"></iframe>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('invoice-pdf-modal');
            const frame = document.getElementById('invoice-pdf-frame');
            const openButton = document.getElementById('open-invoice-pdf-modal');
            const closeButton = document.getElementById('invoice-pdf-close');

            if (! modal || ! frame || ! openButton || ! closeButton) {
                return;
            }

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                frame.src = 'about:blank';
            };

            let printWhenReady = false;

            openButton.addEventListener('click', () => {
                printWhenReady = true;
                frame.src = openButton.dataset.invoicePdfUrl;
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            });

            frame.addEventListener('load', () => {
                if (! printWhenReady || frame.src === 'about:blank') {
                    return;
                }

                printWhenReady = false;

                setTimeout(() => {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (error) {
                        window.print();
                    }
                }, 500);
            });
            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>

    <style>
        .print-sheet {
            display: none;
        }

        .screen-preview {
            max-width: min(1320px, calc(100vw - 32px));
            margin: 0 auto;
        }

        .invoice-copy {
            background: #fff;
            border: 1.8px solid #bcd5d0;
            border-radius: 10px;
            color: #1f3533;
            font-family: Arial, Helvetica, sans-serif;
            padding: clamp(28px, 3vw, 46px);
        }

        .invoice-head {
            align-items: flex-start;
            border-bottom: 1.5px solid #bcd5d0;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 14px;
        }

        .invoice-logo {
            height: 84px;
            object-fit: contain;
            object-position: left top;
            width: 190px;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-copy-label,
        .invoice-section-title {
            color: #5e817b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .invoice-title {
            color: #5b9389;
            font-size: 14px;
            font-weight: 800;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .invoice-number {
            color: #4f8b82;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.05;
        }

        .invoice-date {
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .invoice-parties {
            display: grid;
            gap: 18px;
            grid-template-columns: 1fr 1fr;
            padding: 14px 0;
            border-bottom: 1.5px solid #d6e5e2;
            font-size: 12px;
            line-height: 1.35;
        }

        .invoice-name {
            color: #102927;
            font-size: 14px;
            font-weight: 800;
            margin: 5px 0;
        }

        .invoice-table {
            border-collapse: collapse;
            margin-top: 14px;
            width: 100%;
        }

        .invoice-table th {
            border-bottom: 1.5px solid #bcd5d0;
            color: #5e817b;
            font-size: 10px;
            padding: 0 0 7px;
            text-align: left;
            text-transform: uppercase;
        }

        .invoice-table th:nth-child(2),
        .invoice-table th:nth-child(3),
        .invoice-table td:nth-child(2),
        .invoice-table td:nth-child(3) {
            text-align: right;
        }

        .invoice-table td {
            border-bottom: 1px solid #d6e5e2;
            font-size: 12px;
            padding: 10px 0;
            vertical-align: top;
        }

        .invoice-table td span {
            color: #5e817b;
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .invoice-bottom {
            display: grid;
            gap: 18px;
            grid-template-columns: 1fr 260px;
            margin-top: 14px;
        }

        .invoice-payment {
            color: #334b48;
            font-size: 11px;
            line-height: 1.6;
        }

        .invoice-totals {
            font-size: 12px;
        }

        .invoice-totals p {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin: 0 0 5px;
        }

        .invoice-total {
            border-top: 1.8px solid #bcd5d0;
            color: #4f8b82;
            font-size: 16px;
            font-weight: 800;
            margin-top: 8px !important;
            padding-top: 8px;
        }

        .invoice-footer {
            border-top: 1.5px solid #d6e5e2;
            color: #5f6f6d;
            font-size: 9.5px;
            line-height: 1.35;
            margin-top: 16px;
            min-height: 112px;
            padding-top: 10px;
            position: relative;
        }

        .invoice-courtesy {
            color: #1f3533;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .invoice-signature {
            bottom: 2px;
            position: absolute;
            right: 0;
            text-align: center;
            width: 172px;
        }

        .invoice-signature p {
            color: #1f3533;
            font-size: 10px;
            font-weight: 800;
            margin: 0 0 2px;
        }

        .invoice-signature img {
            display: block;
            height: 42px;
            margin-left: auto;
            max-width: 172px;
            object-fit: contain;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 6mm;
            }

            body {
                background: #fff !important;
            }

            .screen-preview {
                display: none !important;
            }

            .app-section {
                max-width: none !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .print-sheet {
                display: grid;
                grid-template-columns: 1fr 1fr;
                column-gap: 10mm;
                height: 198mm;
                position: relative;
                width: 285mm;
            }

            .cut-line {
                border-left: 1px dashed #8a8a8a;
                height: 100%;
                left: 50%;
                position: absolute;
                top: 0;
            }

            .invoice-copy {
                border: 1px solid #9fbfba;
                border-radius: 4px;
                box-shadow: none !important;
                height: 100%;
                overflow: hidden;
                padding: 7mm;
            }

            .invoice-logo {
                height: 26mm;
                width: 54mm;
            }

            .invoice-number {
                font-size: 18pt;
            }

            .invoice-title {
                font-size: 10pt;
            }

            .invoice-copy-label,
            .invoice-section-title,
            .invoice-table th {
                font-size: 7pt;
            }

            .invoice-parties,
            .invoice-table td,
            .invoice-totals {
                font-size: 8pt;
            }

            .invoice-name {
                font-size: 9pt;
            }

            .invoice-bottom {
                grid-template-columns: 1fr 45mm;
            }

            .invoice-footer {
                font-size: 6.8pt;
                min-height: 28mm;
            }

            .invoice-signature {
                width: 34mm;
            }

            .invoice-signature p {
                font-size: 7pt;
            }

            .invoice-signature img {
                height: 10mm;
                max-width: 34mm;
            }
        }
    </style>
</body>
</html>


