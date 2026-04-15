<x-mail::message>
# Cotygodniowa checklista parafii

**Parafia:** {{ $report['parish']['name'] }}  
**Miasto:** {{ $report['parish']['city'] ?: 'Brak danych' }}  
**Odbiorca:** {{ $report['recipient']['name'] }}  
**Wygenerowano:** {{ $report['generated_at']->format('d.m.Y H:i') }}

## 1. Checklista na najbliższe dni

### Kalendarz mszalny
<x-mail::panel>
**{{ $report['checklist']['mass_calendar']['headline'] }}**  
{{ $report['checklist']['mass_calendar']['description'] }}
</x-mail::panel>

@if (! empty($report['checklist']['mass_calendar']['missing_days']))
Brakujące dni:
@foreach ($report['checklist']['mass_calendar']['missing_days'] as $day)
- {{ $day }}
@endforeach
@endif

### Ogłoszenia parafialne
<x-mail::panel>
**{{ $report['checklist']['announcements']['headline'] }}**  
{{ $report['checklist']['announcements']['description'] }}
</x-mail::panel>

### Kancelaria online
<x-mail::panel>
**{{ $report['checklist']['office']['headline'] }}**  
{{ $report['checklist']['office']['description'] }}
</x-mail::panel>

### Aktualności na stronie
<x-mail::panel>
**{{ $report['checklist']['news']['headline'] }}**  
{{ $report['checklist']['news']['description'] }}
</x-mail::panel>

## 2. Aktualna sytuacja parafii we Wspólnocie

- Aktywni parafianie: **{{ number_format($report['stats']['parishioners_total'], 0, ',', ' ') }}**
- Zatwierdzeni parafianie: **{{ number_format($report['stats']['parishioners_verified'], 0, ',', ' ') }}**
- Aktywni administratorzy parafii: **{{ number_format($report['stats']['admins_total'], 0, ',', ' ') }}**
- Wszystkie zestawy ogłoszeń: **{{ number_format($report['stats']['announcement_sets_total'], 0, ',', ' ') }}**
- Opublikowane zestawy ogłoszeń: **{{ number_format($report['stats']['announcement_sets_published'], 0, ',', ' ') }}**
- Wszystkie msze w systemie: **{{ number_format($report['stats']['masses_total'], 0, ',', ' ') }}**
- Msze zaplanowane na najbliższe 10 dni: **{{ number_format($report['stats']['masses_next_10_days'], 0, ',', ' ') }}**
- Wszystkie aktualności: **{{ number_format($report['stats']['news_total'], 0, ',', ' ') }}**
- Aktualności opublikowane w ostatnich 30 dniach: **{{ number_format($report['stats']['news_published_30d'], 0, ',', ' ') }}**
- Otwarte konwersacje przypisane do Ciebie: **{{ number_format($report['stats']['office_open_for_priest'], 0, ',', ' ') }}**
- Nieprzeczytane lub czekające na reakcję: **{{ number_format($report['stats']['office_unread_for_priest'], 0, ',', ' ') }}**

W razie potrzeby wszystkie te obszary można od razu skorygować z panelu administratora parafii.

Pozdrawiamy,<br>
{{ config('app.name') }}
</x-mail::message>
