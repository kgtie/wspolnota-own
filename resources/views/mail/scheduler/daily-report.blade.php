<x-mail::message>
# Raport harmonogramu zadań

Data raportu: {{ $report['date_label'] }}  
Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}

@foreach ($report['jobs'] as $job)
## {{ $job['label'] }}

- Komenda: `{{ $job['command'] }}`
- Uruchomienia: {{ $job['runs'] }}
- Przebiegi bez zmian: {{ $job['noop_runs'] }}
- Zakończone przebiegi: {{ $job['completed_runs'] }}

@if (! empty($job['metrics']))
<x-mail::panel>
@foreach ($job['metrics'] as $label => $value)
**{{ $label }}:** {{ $value }}  
@endforeach
</x-mail::panel>
@endif

@if ($job['latest_error'])
Ostatni błąd: `{{ $job['latest_error'] }}`
@endif

@endforeach

@if ($report['has_failures'])
<x-mail::panel>
W raporcie wykryto błędy. Sprawdź logi aplikacji i dziennik aktywności w panelu.
</x-mail::panel>
@else
Wszystkie zadania z raportu zakończyły się bez błędów krytycznych.
@endif

Pozdrawiamy,<br>
{{ config('app.name') }}
</x-mail::message>
