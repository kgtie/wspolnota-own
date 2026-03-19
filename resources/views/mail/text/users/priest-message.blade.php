Wiadomosc od proboszcza parafii

Parafia: {{ $parishName ?? 'Nie okreslono' }}
Nadawca: {{ $sender->full_name ?: $sender->name }}
Email nadawcy: {{ $sender->email }}

Tresc:
{{ $messageBody }}
