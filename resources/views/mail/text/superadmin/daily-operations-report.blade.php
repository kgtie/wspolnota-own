Dobowy raport operacyjny Wspólnoty

Data raportu: {{ $report['date_label'] }}
Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}

Podsumowanie:
Nowi użytkownicy: {{ $report['overview']['new_users'] }}
Nowe parafie: {{ $report['overview']['new_parishes'] }}
Opublikowane aktualności: {{ $report['overview']['published_news'] }}
Opublikowane ogłoszenia: {{ $report['overview']['published_announcements'] }}
Nowe / odprawiane msze: {{ $report['overview']['masses_created'] }} / {{ $report['overview']['masses_celebrated'] }}
Kancelaria online: {{ $report['overview']['office_conversations'] }} / {{ $report['overview']['office_messages'] }}
Push wysłane / błędne: {{ $report['overview']['push_sent'] }} / {{ $report['overview']['push_failed'] }}
Nieudane zadania: {{ $report['overview']['failed_jobs'] }}
Wpisy aktywności: {{ $report['overview']['activity_entries'] }}
Wgrane media: {{ $report['overview']['media_uploaded'] }}

@if (! empty($report['users']['items']))
Najważniejsze nowe konta:
@foreach ($report['users']['items'] as $user)
- {{ $user['name'] }} ({{ $user['email'] }}) | {{ $user['role'] }}@if ($user['parish']) | {{ $user['parish'] }}@endif | Zatwierdzony: {{ $user['verified'] ? 'tak' : 'nie' }}
@endforeach
@endif

@if (! empty($report['system']['failed_jobs']))
Nieudane zadania:
@foreach ($report['system']['failed_jobs'] as $job)
- {{ $job['failed_at'] }} | kolejka: {{ $job['queue'] }} | {{ $job['exception'] }}
@endforeach
@endif
