<x-mail::message>
# Opublikowano nowe ogloszenia parafialne

W parafii **{{ $parishName }}** opublikowano nowy zestaw ogloszen, ktory obowiazuje od {{ $announcementSet->effective_from?->format('d.m.Y') }}.

**Tytul zestawu:** {{ $announcementSet->title }}

@if($announcementSet->summary_ai)
**Krotkie streszczenie:** {{ $announcementSet->summary_ai }}
@endif

Ogloszenia sa dostepne w aplikacji mobilnej Wspolnota.

Z Bogiem,  
Wspolnota
</x-mail::message>
