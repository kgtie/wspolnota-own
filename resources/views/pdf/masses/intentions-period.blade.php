<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Intencje mszalne</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
        }

        .header {
            margin-bottom: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 3px;
        }

        .subtitle {
            margin: 0;
            font-size: 11px;
            color: #334155;
        }

        .meta {
            margin-top: 6px;
            font-size: 9px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .02em;
            text-align: left;
        }

        tbody td {
            border: 1px solid #e2e8f0;
            padding: 4px;
            vertical-align: top;
        }

        .w-date { width: 16%; }
        .w-intention { width: 34%; }
        .w-kind { width: 12%; }
        .w-type { width: 10%; }
        .w-priest { width: 16%; }
        .w-money { width: 12%; text-align: right; }

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Intencje mszalne do wydruku</h1>
        <p class="subtitle">
            {{ $parishName }} |
            Okres: {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }} |
            Liczba mszy: {{ $masses->count() }}
        </p>
        <p class="meta">Wygenerowano: {{ $generatedAt->format('d.m.Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="w-date">Termin</th>
                <th class="w-intention">Intencja</th>
                <th class="w-kind">Rodzaj</th>
                <th class="w-type">Typ</th>
                <th class="w-priest">Celebrans</th>
                <th class="w-money">Stypendium</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($masses as $mass)
                <tr>
                    <td class="w-date">
                        <strong>{{ $mass->celebration_at?->format('D') }}</strong><br>
                        {{ $mass->celebration_at?->format('d.m.Y H:i') }}
                    </td>
                    <td class="w-intention">
                        {{ $mass->intention_title }}
                        <br>
                        <span class="muted">Status: {{ $statuses[$mass->status] ?? $mass->status }}</span>
                    </td>
                    <td class="w-kind">{{ $kinds[$mass->mass_kind] ?? $mass->mass_kind }}</td>
                    <td class="w-type">{{ $types[$mass->mass_type] ?? $mass->mass_type }}</td>
                    <td class="w-priest">{{ $mass->celebrant_name ?: 'Brak' }}</td>
                    <td class="w-money">
                        @if ($mass->stipendium_amount !== null)
                            {{ number_format((float) $mass->stipendium_amount, 2, ',', ' ') }} PLN
                        @else
                            <span class="muted">Brak</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
