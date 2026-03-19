<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Komunikacja parafialna</div>
    <h1 style="margin:10px 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Wiadomosc do uczestnikow mszy swietej.</h1>

    <div style="padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#475569;">
        <strong>Parafia:</strong> {{ $parishName ?? 'Nie okreslono' }}<br>
        <strong>Nadawca:</strong> {{ $sender->full_name ?: $sender->name }}<br>
        <strong>Email nadawcy:</strong> {{ $sender->email }}<br>
        <strong>Msza:</strong> {{ $mass->celebration_at?->format('d.m.Y H:i') ?? 'Brak terminu' }}<br>
        <strong>Intencja:</strong> {{ $mass->intention_title }}
    </div>

    <div style="margin-top:18px;font-size:16px;line-height:1.8;color:#57534e;">
        {!! nl2br(e($messageBody)) !!}
    </div>
</div>
