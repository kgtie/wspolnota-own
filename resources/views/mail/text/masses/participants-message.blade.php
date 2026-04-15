Wiadomosc do uczestnikow mszy swietej

Parafia: {{ $parishName ?? 'Nie okreslono' }}
Nadawca: {{ $sender->full_name ?: $sender->name }}
Email nadawcy: {{ $sender->email }}
Msza: {{ $mass->celebration_at?->format('d.m.Y H:i') ?? 'Brak terminu' }}
Intencja: {{ $mass->intention_title }}

Tresc:
{{ $messageBody }}
