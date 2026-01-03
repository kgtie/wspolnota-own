<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 8px 0;
        }

        .meta {
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f3f3;
        }

        .time {
            white-space: nowrap;
            width: 120px;
        }

        .loc {
            width: 140px;
        }
    </style>
</head>

<body>
    <h1>Msze święte — {{ $parish->name }}</h1>
    <div class="meta">
        Zakres: {{ $from->format('d.m.Y') }} – {{ $to->format('d.m.Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="time">Data i godz.</th>
                <th class="loc">Miejsce</th>
                <th>Intencja</th>
                <th>Rodzaj / ryt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($masses as $mass)
                <tr>
                    <td class="time">{{ \Illuminate\Support\Carbon::parse($mass->start_time)->format('d.m.Y H:i') }}</td>
                    <td class="loc">{{ $mass->location }}</td>
                    <td>{!! nl2br(e($mass->intention)) !!}</td>
                    <td>{{ $mass->getTypeLabel() }} / {{ $mass->getRiteLabel() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Brak mszy w tym okresie.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>