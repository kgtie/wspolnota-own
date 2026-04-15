Cotygodniowa checklista parafii

Parafia: {{ $report['parish']['name'] }}
Miasto: {{ $report['parish']['city'] ?: 'Brak danych' }}
Odbiorca: {{ $report['recipient']['name'] }}
Wygenerowano: {{ $report['generated_at']->format('d.m.Y H:i') }}

@foreach ($report['checklist'] as $section)
{{ $section['headline'] }}
{{ $section['description'] }}
@if (! empty($section['missing_days']))
Brakujące dni:
@foreach ($section['missing_days'] as $day)
- {{ $day }}
@endforeach
@endif

@endforeach
Statystyki:
Aktywni parafianie: {{ number_format($report['stats']['parishioners_total'], 0, ',', ' ') }}
Zatwierdzeni parafianie: {{ number_format($report['stats']['parishioners_verified'], 0, ',', ' ') }}
Administratorzy parafii: {{ number_format($report['stats']['admins_total'], 0, ',', ' ') }}
Wszystkie zestawy ogłoszeń: {{ number_format($report['stats']['announcement_sets_total'], 0, ',', ' ') }}
Opublikowane zestawy ogłoszeń: {{ number_format($report['stats']['announcement_sets_published'], 0, ',', ' ') }}
Wszystkie msze: {{ number_format($report['stats']['masses_total'], 0, ',', ' ') }}
Msze na 10 dni: {{ number_format($report['stats']['masses_next_10_days'], 0, ',', ' ') }}
Wszystkie aktualności: {{ number_format($report['stats']['news_total'], 0, ',', ' ') }}
Aktualności z 30 dni: {{ number_format($report['stats']['news_published_30d'], 0, ',', ' ') }}
Otwarte konwersacje kancelarii: {{ number_format($report['stats']['office_open_for_priest'], 0, ',', ' ') }}
Nieprzeczytane lub czekające: {{ number_format($report['stats']['office_unread_for_priest'], 0, ',', ' ') }}
