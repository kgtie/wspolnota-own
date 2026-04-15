<x-mail::message>
# Opublikowano nowe ogłoszenia parafialne

W parafii **{{ $parishName }}** opublikowano nowy zestaw ogłoszeń, który obowiązuje od {{ $announcementSet->effective_from?->format('d.m.Y') }}.

**Tytuł zestawu:** {{ $announcementSet->title }}

@if($announcementSet->summary_ai)
**Krótkie streszczenie:** {{ $announcementSet->summary_ai }}
@endif

Ogłoszenia są dostępne w aplikacji mobilnej Wspólnota.

Z Bogiem,  
Wspólnota
</x-mail::message>
