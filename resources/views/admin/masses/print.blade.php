<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <title>Intencje Mszalne - {{ $parish->short_name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
            color: #000;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            margin-top: 0;
            color: #555;
        }

        .period {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .day-block {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .day-header {
            background-color: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .time-col {
            width: 80px;
            font-weight: bold;
        }

        .loc-col {
            width: 120px;
            font-size: 0.9em;
            color: #666;
        }

        .intent-col {}

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Drukuj / Zapisz jako PDF</button>
    </div>

    <h1>{{ $parish->name }}</h1>
    <h2>{{ $parish->city }}, {{ $parish->street }}</h2>

    <div class="period">
        Intencje mszalne w okresie: {{ $request->date_from }} - {{ $request->date_to }}
    </div>

    @foreach($masses as $date => $dayMasses)
        <div class="day-block">
            <div class="day-header">
                {{ \Carbon\Carbon::parse($date)->locale('pl')->isoFormat('dddd, D MMMM YYYY') }}
            </div>
            <table>
                @foreach($dayMasses as $mass)
                    <tr>
                        <td class="time-col">{{ $mass->start_time->format('H:i') }}</td>
                        <td class="loc-col">
                            {{ $mass->location }}<br>
                            @if($mass->celebrant) <i>ks. {{ $mass->celebrant }}</i> @endif
                        </td>
                        <td class="intent-col">
                            {{ $mass->intention }}
                            @if($mass->type !== 'z dnia')
                                <span style="font-size: 0.8em; color: #888;">[{{ $mass->type }}]</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div style="margin-top: 50px; font-size: 12px; text-align: center; color: #aaa;">
        Wygenerowano z systemu "Wspólnota" dnia {{ now()->format('Y-m-d H:i') }}
    </div>

</body>

</html>