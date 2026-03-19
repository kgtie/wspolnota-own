Dobowy raport operacyjny Wspolnota

Data raportu: {{ $report['date_label'] }}
Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}

Executive summary:
Nowi uzytkownicy: {{ $report['overview']['new_users'] }}
Nowe parafie: {{ $report['overview']['new_parishes'] }}
Opublikowane aktualnosci: {{ $report['overview']['published_news'] }}
Opublikowane ogloszenia: {{ $report['overview']['published_announcements'] }}
Nowe / odprawiane msze: {{ $report['overview']['masses_created'] }} / {{ $report['overview']['masses_celebrated'] }}
Kancelaria online: {{ $report['overview']['office_conversations'] }} / {{ $report['overview']['office_messages'] }}
Push sent / failed: {{ $report['overview']['push_sent'] }} / {{ $report['overview']['push_failed'] }}
Failed jobs: {{ $report['overview']['failed_jobs'] }}
Activity entries: {{ $report['overview']['activity_entries'] }}
Wgrane media: {{ $report['overview']['media_uploaded'] }}

@if (! empty($report['users']['items']))
Najwazniejsze nowe konta:
@foreach ($report['users']['items'] as $user)
- {{ $user['name'] }} ({{ $user['email'] }}) | {{ $user['role'] }}@if ($user['parish']) | {{ $user['parish'] }}@endif | Zatwierdzony: {{ $user['verified'] ? 'tak' : 'nie' }}
@endforeach
@endif

@if (! empty($report['system']['failed_jobs']))
Failed jobs:
@foreach ($report['system']['failed_jobs'] as $job)
- {{ $job['failed_at'] }} | queue: {{ $job['queue'] }} | {{ $job['exception'] }}
@endforeach
@endif
