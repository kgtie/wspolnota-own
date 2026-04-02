Raport harmonogramu zadań

Data raportu: {{ $report['date_label'] }}
Zakres: {{ $report['window']['start']->format('d.m.Y H:i') }} - {{ $report['window']['end']->format('d.m.Y H:i') }}

@foreach ($report['jobs'] as $job)
{{ $job['label'] }}
Komenda: {{ $job['command'] }}
Uruchomienia: {{ $job['runs'] }}
Przebiegi bez zmian: {{ $job['noop_runs'] }}
Zakończone przebiegi: {{ $job['completed_runs'] }}
@if (! empty($job['metrics']))
@foreach ($job['metrics'] as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@endif
@if ($job['latest_error'])
Ostatni błąd: {{ $job['latest_error'] }}
@endif

@endforeach
