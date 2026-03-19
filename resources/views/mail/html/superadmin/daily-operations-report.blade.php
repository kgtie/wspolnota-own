<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Raport operacyjny</div>
    <h1 style="margin:10px 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Dobowy raport operacyjny Wspolnota.</h1>

    <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#475569;">
        <strong>Data raportu:</strong> {{ $report['date_label'] }}<br>
        <strong>Zakres:</strong> {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}
    </div>

    <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#f8f3ec;border:1px solid #eadfce;">
        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#8e5520;font-weight:700;">Executive summary</div>
        <div style="margin-top:10px;font-size:15px;line-height:1.8;color:#57534e;">
            <strong>Nowi uzytkownicy:</strong> {{ $report['overview']['new_users'] }}<br>
            <strong>Nowe parafie:</strong> {{ $report['overview']['new_parishes'] }}<br>
            <strong>Opublikowane aktualnosci:</strong> {{ $report['overview']['published_news'] }}<br>
            <strong>Opublikowane ogloszenia:</strong> {{ $report['overview']['published_announcements'] }}<br>
            <strong>Nowe / odprawiane msze:</strong> {{ $report['overview']['masses_created'] }} / {{ $report['overview']['masses_celebrated'] }}<br>
            <strong>Kancelaria online:</strong> {{ $report['overview']['office_conversations'] }} / {{ $report['overview']['office_messages'] }}<br>
            <strong>Push sent / failed:</strong> {{ $report['overview']['push_sent'] }} / {{ $report['overview']['push_failed'] }}<br>
            <strong>Failed jobs:</strong> {{ $report['overview']['failed_jobs'] }}<br>
            <strong>Activity entries:</strong> {{ $report['overview']['activity_entries'] }}<br>
            <strong>Wgrane media:</strong> {{ $report['overview']['media_uploaded'] }}
        </div>
    </div>

    @if (! empty($report['users']['items']))
        <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;">
            <div style="font-size:20px;line-height:1.35;color:#1f2937;font-weight:700;">Najwazniejsze nowe konta</div>
            <div style="margin-top:10px;font-size:14px;line-height:1.8;color:#57534e;">
                @foreach ($report['users']['items'] as $user)
                    {{ $user['name'] }} ({{ $user['email'] }}) | {{ $user['role'] }}@if ($user['parish']) | {{ $user['parish'] }}@endif | Zatwierdzony: {{ $user['verified'] ? 'tak' : 'nie' }}<br>
                @endforeach
            </div>
        </div>
    @endif

    @if (! empty($report['content']['news']) || ! empty($report['content']['announcements']) || ! empty($report['content']['masses_created']))
        <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;">
            <div style="font-size:20px;line-height:1.35;color:#1f2937;font-weight:700;">Tresci i liturgia</div>
            <div style="margin-top:10px;font-size:14px;line-height:1.8;color:#57534e;">
                <strong>Aktualnosci:</strong> {{ $report['overview']['published_news'] }}<br>
                <strong>Ogloszenia:</strong> {{ $report['overview']['published_announcements'] }}<br>
                <strong>Utworzone msze:</strong> {{ $report['overview']['masses_created'] }}<br>
                <strong>Msze w dobie raportu:</strong> {{ $report['overview']['masses_celebrated'] }}
            </div>
        </div>
    @endif

    @if (! empty($report['system']['failed_jobs']))
        <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fff1f2;border:1px solid #fecdd3;">
            <div style="font-size:20px;line-height:1.35;color:#9f1239;font-weight:700;">Failed jobs</div>
            <div style="margin-top:10px;font-size:14px;line-height:1.8;color:#881337;">
                @foreach ($report['system']['failed_jobs'] as $job)
                    {{ $job['failed_at'] }} | queue: {{ $job['queue'] }} | {{ $job['exception'] }}<br>
                @endforeach
            </div>
        </div>
    @endif

    <p style="margin:0;font-size:15px;line-height:1.8;color:#57534e;">
        To jest raport zbiorczy z calosci danych, ktore backend Wspolnoty obecnie sledzi i zapisuje.
    </p>
</div>
