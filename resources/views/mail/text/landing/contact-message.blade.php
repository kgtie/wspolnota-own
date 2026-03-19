Nowa wiadomosc z formularza kontaktowego

Nadawca: {{ $name }}
Email: {{ $email }}
@if (filled($parish))
Parafia: {{ $parish }}
@endif
@if (filled($phone))
Telefon: {{ $phone }}
@endif
Temat: {{ $subjectLine }}

Tresc:
{{ $messageBody }}
