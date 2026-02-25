<x-mail::message>
# Wiadomosc do uczestnikow mszy swietej

Ta wiadomosc zostala wyslana przez proboszcza z panelu parafialnego Wspolnoty.

**Parafia:** {{ $parishName ?? 'Nie okreslono' }}  
**Nadawca:** {{ $sender->full_name ?: $sender->name }}  
**Email nadawcy:** {{ $sender->email }}

---

**Msza:** {{ $mass->celebration_at?->format('d.m.Y H:i') ?? 'Brak terminu' }}  
**Intencja:** {{ $mass->intention_title }}

---

{!! nl2br(e($messageBody)) !!}

---

W razie pytan odpowiedz bezposrednio na ten email lub skontaktuj sie z kancelaria parafii.

Z Bogiem,  
Wspolnota
</x-mail::message>
