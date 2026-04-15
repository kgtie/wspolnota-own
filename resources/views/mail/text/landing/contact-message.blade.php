Nowa wiadomość z formularza kontaktowego

Nadawca: {{ $name }}
Adres e-mail: {{ $email }}
@if (filled($parish))
Parafia: {{ $parish }}
@endif
@if (filled($phone))
Telefon: {{ $phone }}
@endif
Temat: {{ $subjectLine }}

Treść:
{{ $messageBody }}
