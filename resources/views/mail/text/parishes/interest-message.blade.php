Nowe zainteresowanie uruchomieniem uslugi

Parafia: {{ $parish->name }}
Krotka nazwa: {{ $parish->short_name }}
Slug: {{ $parish->slug }}
Miejscowosc: {{ $parish->city }}
Data zgloszenia: {{ $requestedAt->translatedFormat('j F Y, H:i') }}
@if (filled($parish->email))
Email parafii: {{ $parish->email }}
@endif
@if (filled($parish->phone))
Telefon parafii: {{ $parish->phone }}
@endif
Publiczny adres strony: {{ $publicUrl }}
@if (filled($requesterIp))
IP zgloszenia: {{ $requesterIp }}
@endif
@if (filled($userAgent))
User Agent: {{ $userAgent }}
@endif
