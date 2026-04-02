<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Raport operacyjny</div>
    <h1 style="margin:10px 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Raport harmonogramu zadań.</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.8;color:#475569;">
        Data raportu: <strong>{{ $report['date_label'] }}</strong><br>
        Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}
    </p>

    @foreach ($report['jobs'] as $job)
        <div style="margin-bottom:18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;">
            <div style="font-size:20px;line-height:1.4;color:#1f2937;font-weight:700;">{{ $job['label'] }}</div>
            <div style="margin-top:10px;font-size:14px;line-height:1.8;color:#57534e;">
                <strong>Komenda:</strong> {{ $job['command'] }}<br>
                <strong>Uruchomienia:</strong> {{ $job['runs'] }}<br>
                <strong>Przebiegi bez zmian:</strong> {{ $job['noop_runs'] }}<br>
                <strong>Zakończone przebiegi:</strong> {{ $job['completed_runs'] }}
            </div>

            @if (! empty($job['metrics']))
                <div style="margin-top:12px;font-size:14px;line-height:1.8;color:#475569;">
                    @foreach ($job['metrics'] as $label => $value)
                        <strong>{{ $label }}:</strong> {{ $value }}<br>
                    @endforeach
                </div>
            @endif

            @if ($job['latest_error'])
                <div style="margin-top:12px;font-size:14px;line-height:1.7;color:#9f1239;">
                    <strong>Ostatni błąd:</strong> {{ $job['latest_error'] }}
                </div>
            @endif
        </div>
    @endforeach

    <p style="margin:0;font-size:15px;line-height:1.8;color:#57534e;">
        @if ($report['has_failures'])
            W raporcie wykryto błędy. Sprawdź logi aplikacji i dziennik aktywności.
        @else
            Wszystkie zadania z raportu zakończyły się bez błędów krytycznych.
        @endif
    </p>
</div>
