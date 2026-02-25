<x-mail::message>
# Opublikowano nowe ogloszenia parafialne

W parafii **{{ $parishName }}** opublikowano nowy zestaw ogloszen, ktory obowiazuje od {{ $announcementSet->effective_from?->format('d.m.Y') }}.

**Tytul zestawu:** {{ $announcementSet->title }}

@if($announcementSet->summary_ai)
**Krotkie streszczenie:** {{ $announcementSet->summary_ai }}
@endif

<x-mail::button :url="$announcementsUrl">
Zobacz ogloszenia
</x-mail::button>

Ogloszenia sa dostepne w aplikacji mobilnej i webowej Wspolnota.

Z Bogiem,  
Wspolnota
</x-mail::message>
