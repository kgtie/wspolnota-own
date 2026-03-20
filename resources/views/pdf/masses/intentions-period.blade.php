<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Intencje mszalne</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 11mm 11mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .page {
            min-height: 100%;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d6d3d1;
        }

        .brand-cell,
        .service-cell,
        .header-copy {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-cell,
        .service-cell {
            width: 66px;
        }

        .logo-box {
            width: 54px;
            height: 54px;
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            background: #fffdf8;
            text-align: center;
            vertical-align: middle;
        }

        .logo-box img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 14px;
        }

        .logo-fallback {
            display: inline-block;
            width: 54px;
            line-height: 54px;
            font-size: 8px;
            color: #a8a29e;
        }

        .header-copy {
            padding: 0 8px;
        }

        .eyebrow {
            margin: 0 0 4px;
            font-size: 8px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #b45309;
        }

        .title {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }

        .subtitle {
            margin: 0;
            font-size: 10px;
            color: #4b5563;
            line-height: 1.35;
        }

        .summary-strip {
            margin-bottom: 10px;
            padding: 7px 9px;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            padding: 6px 7px;
            border: 1px solid #d6d3d1;
            background: #f8fafc;
            color: #44403c;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            text-align: left;
        }

        tbody td {
            padding: 6px 7px;
            border: 1px solid #e7e5e4;
            vertical-align: top;
            font-size: 9px;
            line-height: 1.35;
        }

        .col-when {
            width: 22%;
            color: #92400e;
            font-weight: 700;
        }

        .col-intention {
            width: 32%;
        }

        .col-details {
            width: 46%;
        }

        .intention-title {
            margin: 0;
            font-size: 10px;
            font-weight: 700;
            color: #111827;
        }

        .details {
            margin: 0;
            color: #374151;
            line-height: 1.45;
            white-space: pre-line;
        }

        .details.empty {
            color: #9ca3af;
            font-style: italic;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #d6d3d1;
            font-size: 8px;
            line-height: 1.45;
            color: #6b7280;
        }

        .footer strong {
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="brand-cell">
                <div class="logo-box">
                    @if ($parish_logo_data_uri)
                        <img src="{{ $parish_logo_data_uri }}" alt="Logo parafii">
                    @else
                        <span class="logo-fallback">PARAFIA</span>
                    @endif
                </div>
            </div>

            <div class="header-copy">
                <p class="eyebrow">Wydruk parafialny</p>
                <h1 class="title">Intencje mszalne</h1>
                <p class="subtitle">
                    {{ $parish->name }}<br>
                    Zakres: {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}<br>
                    Liczba wpisow: {{ $masses->count() }}
                </p>
            </div>

            <div class="service-cell">
                <div class="logo-box">
                    <img src="{{ $service_logo_data_uri }}" alt="Logo Wspolnota">
                </div>
            </div>
        </div>

        <div class="summary-strip">
            Zestawienie obejmuje jedynie termin mszy, glowna intencje oraz opis intencji przygotowany do wydruku kancelaryjnego.
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-when">Kiedy</th>
                    <th class="col-intention">Intencja</th>
                    <th class="col-details">Opis intencji</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($masses as $mass)
                    <tr>
                        <td class="col-when">
                            {{ $mass->celebration_at?->format('d.m.Y') }}<br>
                            godz. {{ $mass->celebration_at?->format('H:i') }}
                        </td>
                        <td class="col-intention">
                            <p class="intention-title">{{ $mass->intention_title }}</p>
                        </td>
                        <td class="col-details">
                            <p class="details{{ blank($mass->intention_details) ? ' empty' : '' }}">
                                {{ $mass->intention_details ?: 'Brak dodatkowego opisu intencji.' }}
                            </p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <strong>Wygenerowano:</strong> {{ $generatedAt->format('d.m.Y H:i') }}<br>
            Usluga Wspolnota jest dostepna w App Store oraz Google Play Store.
        </div>
    </div>
</body>
</html>
