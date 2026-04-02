Nowy zestaw ogłoszeń parafialnych

Parafia: {{ $parishName }}
Obowiązuje od: {{ $announcementSet->effective_from?->format('d.m.Y') ?? 'dzisiaj' }}
Tytuł: {{ $announcementSet->title }}
@if ($announcementSet->summary_ai)

Streszczenie:
{{ $announcementSet->summary_ai }}
@endif
