<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Checklista parafii</div>
    <h1 style="margin:10px 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Cotygodniowa checklista parafii.</h1>

    <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#475569;">
        <strong>Parafia:</strong> {{ $report['parish']['name'] }}<br>
        <strong>Miasto:</strong> {{ $report['parish']['city'] ?: 'Brak danych' }}<br>
        <strong>Odbiorca:</strong> {{ $report['recipient']['name'] }}<br>
        <strong>Wygenerowano:</strong> {{ $report['generated_at']->format('d.m.Y H:i') }}
    </div>

    @foreach ($report['checklist'] as $section)
        <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;">
            <div style="font-size:20px;line-height:1.35;color:#1f2937;font-weight:700;">{{ $section['headline'] }}</div>
            <p style="margin:10px 0 0;font-size:15px;line-height:1.8;color:#57534e;">{{ $section['description'] }}</p>

            @if (! empty($section['missing_days']))
                <div style="margin-top:12px;font-size:14px;line-height:1.8;color:#475569;">
                    <strong>Brakujace dni:</strong><br>
                    @foreach ($section['missing_days'] as $day)
                        - {{ $day }}<br>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#f8f3ec;border:1px solid #eadfce;">
        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#8e5520;font-weight:700;">Stan parafii we Wspolnocie</div>
        <div style="margin-top:10px;font-size:15px;line-height:1.8;color:#57534e;">
            <strong>Aktywni parafianie:</strong> {{ number_format($report['stats']['parishioners_total'], 0, ',', ' ') }}<br>
            <strong>Zatwierdzeni parafianie:</strong> {{ number_format($report['stats']['parishioners_verified'], 0, ',', ' ') }}<br>
            <strong>Administratorzy parafii:</strong> {{ number_format($report['stats']['admins_total'], 0, ',', ' ') }}<br>
            <strong>Wszystkie zestawy ogloszen:</strong> {{ number_format($report['stats']['announcement_sets_total'], 0, ',', ' ') }}<br>
            <strong>Opublikowane zestawy ogloszen:</strong> {{ number_format($report['stats']['announcement_sets_published'], 0, ',', ' ') }}<br>
            <strong>Wszystkie msze:</strong> {{ number_format($report['stats']['masses_total'], 0, ',', ' ') }}<br>
            <strong>Msze na 10 dni:</strong> {{ number_format($report['stats']['masses_next_10_days'], 0, ',', ' ') }}<br>
            <strong>Wszystkie aktualnosci:</strong> {{ number_format($report['stats']['news_total'], 0, ',', ' ') }}<br>
            <strong>Aktualnosci z 30 dni:</strong> {{ number_format($report['stats']['news_published_30d'], 0, ',', ' ') }}<br>
            <strong>Otwarte konwersacje kancelarii:</strong> {{ number_format($report['stats']['office_open_for_priest'], 0, ',', ' ') }}<br>
            <strong>Nieprzeczytane lub czekajace:</strong> {{ number_format($report['stats']['office_unread_for_priest'], 0, ',', ' ') }}
        </div>
    </div>

    <p style="margin:0;font-size:15px;line-height:1.8;color:#57534e;">
        W razie potrzeby wszystkie te obszary mozna od razu skorygowac z panelu administratora parafii.
    </p>
</div>
