<x-mail::message>
# Dobowy raport operacyjny Wspólnoty

Data raportu: {{ $report['date_label'] }}  
Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}

## Podsumowanie

<x-mail::panel>
Nowi użytkownicy: **{{ $report['overview']['new_users'] }}**  
Nowe parafie: **{{ $report['overview']['new_parishes'] }}**  
Opublikowane aktualności: **{{ $report['overview']['published_news'] }}**  
Opublikowane zestawy ogłoszeń: **{{ $report['overview']['published_announcements'] }}**  
Nowe / odprawiane msze: **{{ $report['overview']['masses_created'] }} / {{ $report['overview']['masses_celebrated'] }}**  
Nowe konwersacje / wiadomości kancelarii: **{{ $report['overview']['office_conversations'] }} / {{ $report['overview']['office_messages'] }}**  
Push wysłane / błędne: **{{ $report['overview']['push_sent'] }} / {{ $report['overview']['push_failed'] }}**  
Nieudane zadania: **{{ $report['overview']['failed_jobs'] }}**  
Wpisy aktywności: **{{ $report['overview']['activity_entries'] }}**  
Wgrane media: **{{ $report['overview']['media_uploaded'] }}**
</x-mail::panel>

## Użytkownicy i parafie

- Nowi użytkownicy: {{ $report['overview']['new_users'] }}
- Zatwierdzenia parafialne: {{ $report['overview']['verified_users'] }}
- Nowe parafie: {{ $report['overview']['new_parishes'] }}
- Nowi parafianie / administratorzy / superadmini: {{ $report['users']['by_role']['parafianie'] }} / {{ $report['users']['by_role']['administratorzy'] }} / {{ $report['users']['by_role']['superadmini'] }}

@if (! empty($report['users']['items']))
Najważniejsze nowe konta:
@foreach ($report['users']['items'] as $user)
- {{ $user['name'] }} ({{ $user['email'] }}) | {{ $user['role'] }}@if($user['parish']) | {{ $user['parish'] }}@endif | Zatwierdzony: {{ $user['verified'] ? 'tak' : 'nie' }}
@endforeach
@endif

## Treści i liturgia

- Opublikowane aktualności: {{ $report['overview']['published_news'] }}
- Opublikowane ogłoszenia: {{ $report['overview']['published_announcements'] }}
- Utworzone msze: {{ $report['overview']['masses_created'] }}
- Msze przypadajace w raportowanej dobie: {{ $report['overview']['masses_celebrated'] }}

@if (! empty($report['content']['news']))
### Aktualności
@foreach ($report['content']['news'] as $item)
- {{ $item['published_at'] }} | {{ $item['parish'] ?? 'Brak parafii' }} | {{ $item['title'] }}
@endforeach
@endif

@if (! empty($report['content']['announcements']))
### Ogłoszenia
@foreach ($report['content']['announcements'] as $item)
- {{ $item['published_at'] }} | {{ $item['parish'] ?? 'Brak parafii' }} | {{ $item['title'] }} | {{ $item['effective'] }}
@endforeach
@endif

@if (! empty($report['content']['masses_created']))
### Nowe msze
@foreach ($report['content']['masses_created'] as $item)
- {{ $item['celebration_at'] ?? 'Brak daty' }} | {{ $item['parish'] ?? 'Brak parafii' }} | {{ $item['intention_title'] ?: 'Bez intencji' }} | Status: {{ $item['status'] }}
@endforeach
@endif

## Kancelaria online

- Nowe konwersacje: {{ $report['overview']['office_conversations'] }}
- Nowe wiadomości: {{ $report['overview']['office_messages'] }}
- Zamknięte konwersacje: {{ $report['office']['closed_in_window'] }}
- Otwarte konwersacje teraz: {{ $report['office']['open_total_now'] }}

@if (! empty($report['office']['new_conversations']))
### Nowe konwersacje
@foreach ($report['office']['new_conversations'] as $item)
- {{ $item['created_at'] }} | {{ $item['parish'] ?? 'Brak parafii' }} | Parafianin: {{ $item['parishioner'] ?: 'brak' }} | Proboszcz: {{ $item['priest'] ?: 'brak' }} | Status: {{ $item['status'] }}
@endforeach
@endif

## Push, feed i jakość komunikacji

- Wygenerowane notification items: {{ $report['overview']['notification_items'] }}
- Push wysłane: {{ $report['overview']['push_sent'] }}
- Push błędne: {{ $report['overview']['push_failed'] }}

@if (! empty($report['push']['by_type']))
### Push by type
@foreach ($report['push']['by_type'] as $type => $row)
- {{ $type }} | sent: {{ $row['sent'] }} | failed: {{ $row['failed'] }} | queued: {{ $row['queued'] }}
@endforeach
@endif

@if (! empty($report['push']['notifications_by_type']))
### Feed notification items by type
@foreach ($report['push']['notifications_by_type'] as $type => $count)
- {{ $type }}: {{ $count }}
@endforeach
@endif

@if (! empty($report['push']['top_failures']))
### Ostatnie błędy push
@foreach ($report['push']['top_failures'] as $failure)
- {{ $failure['when'] }} | {{ $failure['type'] }} | {{ $failure['platform'] ?: 'brak platformy' }} | {{ $failure['error'] ?: 'brak błędu' }}
@endforeach
@endif

## System, kolejka i telemetry

Aktualne łączne stany:
@foreach ($report['system']['totals_now'] as $label => $value)
- {{ str_replace('_', ' ', $label) }}: {{ $value }}
@endforeach

@if (! empty($report['system']['top_activity_events']))
### Najczęstsze zdarzenia w activity logu
@foreach ($report['system']['top_activity_events'] as $event => $count)
- {{ $event }}: {{ $count }}
@endforeach
@endif

@if (! empty($report['system']['top_actors']))
### Najaktywniejsi aktorzy
@foreach ($report['system']['top_actors'] as $actor => $count)
- {{ $actor }}: {{ $count }}
@endforeach
@endif

@if (! empty($report['system']['media_by_collection']))
### Media wgrane w ostatniej dobie
@foreach ($report['system']['media_by_collection'] as $collection => $count)
- {{ $collection }}: {{ $count }}
@endforeach
@endif

@if (! empty($report['system']['failed_jobs']))
### Failed jobs
@foreach ($report['system']['failed_jobs'] as $job)
- {{ $job['failed_at'] }} | queue: {{ $job['queue'] }} | {{ $job['exception'] }}
@endforeach
@endif

To jest raport zbiorczy z calosci danych, ktore backend Wspolnoty obecnie sledzi i zapisuje.

Pozdrawiamy,<br>
{{ config('app.name') }}
</x-mail::message>
