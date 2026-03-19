Nowy zestaw ogloszen parafialnych

Parafia: {{ $parishName }}
Obowiazuje od: {{ $announcementSet->effective_from?->format('d.m.Y') ?? 'dzisiaj' }}
Tytul: {{ $announcementSet->title }}
@if ($announcementSet->summary_ai)

Streszczenie:
{{ $announcementSet->summary_ai }}
@endif
