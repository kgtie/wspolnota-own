<x-mail::message>
# Wiadomość do uczestników mszy świętej

Ta wiadomość została wysłana przez proboszcza z panelu parafialnego Wspólnoty.

**Parafia:** {{ $parishName ?? 'Nie określono' }}  
**Nadawca:** {{ $sender->full_name ?: $sender->name }}  
**E-mail nadawcy:** {{ $sender->email }}

---

**Msza:** {{ $mass->celebration_at?->format('d.m.Y H:i') ?? 'Brak terminu' }}  
**Intencja:** {{ $mass->intention_title }}

---

{!! nl2br(e($messageBody)) !!}

---

W razie pytań odpowiedz bezpośrednio na ten e-mail lub skontaktuj się z kancelarią parafii.

Z Bogiem,  
Wspólnota
</x-mail::message>
