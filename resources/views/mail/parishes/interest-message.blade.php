<x-mail::message>
# Nowe zainteresowanie uruchomieniem usługi

Na publicznej stronie parafii potwierdzono chęć kontaktu w sprawie wdrożenia lub poznania usługi Wspólnota.

**Parafia:** {{ $parish->name }}  
**Krótka nazwa:** {{ $parish->short_name }}  
**Slug:** {{ $parish->slug }}  
**Miejscowość:** {{ $parish->city }}  
**Data zgłoszenia:** {{ $requestedAt->translatedFormat('j F Y, H:i') }}

@if (filled($parish->email))
**Email parafii:** {{ $parish->email }}
@endif

@if (filled($parish->phone))
**Telefon parafii:** {{ $parish->phone }}
@endif

@if (filled($parish->website))
**Strona WWW:** {{ $parish->website }}
@endif

@if (filled($parish->street) || filled($parish->postal_code) || filled($parish->city))
**Adres:** {{ collect([$parish->street, trim(collect([$parish->postal_code, $parish->city])->filter()->implode(' '))])->filter()->implode(', ') }}
@endif

**Publiczny adres strony:** {{ $publicUrl }}

@if (filled($requesterIp))
**IP zgłoszenia:** {{ $requesterIp }}
@endif

@if (filled($userAgent))
**User Agent:** {{ $userAgent }}
@endif

<x-mail::button :url="$publicUrl">
Otwórz stronę parafii
</x-mail::button>

Możesz teraz samodzielnie skontaktować się z parafią i zaproponować dalsze kroki.

{{ config('app.name') }}
</x-mail::message>
