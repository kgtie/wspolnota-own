<x-mail::message>
# Wiadomość od proboszcza parafii

Ta wiadomość została wysłana przez proboszcza z panelu parafialnego Wspólnoty.

**Parafia:** {{ $parishName ?? 'Nie określono' }}  
**Nadawca:** {{ $sender->full_name ?: $sender->name }}  
**Email nadawcy:** {{ $sender->email }}

---

{!! nl2br(e($messageBody)) !!}

---

W razie pytań odpowiedz bezpośrednio na ten email lub skontaktuj się z kancelarią parafii.

Z Bogiem,  
Wspólnota
</x-mail::message>
