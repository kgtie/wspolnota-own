<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Formularz kontaktowy</div>
    <h1 style="margin:10px 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Nowa wiadomosc z landing page.</h1>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #eadfce;border-radius:18px;background-color:#fffdf9;">
        <tr>
            <td style="padding:16px 18px;font-size:15px;line-height:1.8;color:#475569;">
                <strong>Nadawca:</strong> {{ $name }}<br>
                <strong>Email:</strong> {{ $email }}<br>
                @if (filled($parish))
                    <strong>Parafia:</strong> {{ $parish }}<br>
                @endif
                @if (filled($phone))
                    <strong>Telefon:</strong> {{ $phone }}<br>
                @endif
                <strong>Temat:</strong> {{ $subjectLine }}
            </td>
        </tr>
    </table>

    <div style="margin-top:18px;padding:18px;border-radius:18px;background-color:#f8f3ec;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
        {!! nl2br(e($messageBody)) !!}
    </div>
</div>
