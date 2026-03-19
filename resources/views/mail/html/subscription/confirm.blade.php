<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Lista mailingowa</div>
    <h1 style="margin:10px 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:32px;line-height:1.15;color:#1c1917;">Szczesc Boze! Potwierdz swoj adres email.</h1>
    <p style="margin:0 0 14px;font-size:16px;line-height:1.8;color:#475569;">
        Dziekujemy za dolaczenie do newslettera Wspolnoty. To tutaj bedziemy dzielic sie rozwojem uslugi i waznymi nowosciami.
    </p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.8;color:#475569;">
        Zostalo jeszcze tylko jedno potwierdzenie. Jesli to nie Ty zapisywales ten adres, po prostu zignoruj te wiadomosc.
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
        <tr>
            <td>
                <a href="{{ route('landing.mailing.confirm', ['token' => $subscriber->confirmation_token]) }}" style="display:inline-block;padding:12px 20px;border-radius:999px;background-color:#b87333;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                    Potwierdz zapis
                </a>
            </td>
        </tr>
    </table>
    <div style="padding:16px 18px;border-radius:18px;background-color:#f8f3ec;border:1px solid #eadfce;font-size:14px;line-height:1.8;color:#57534e;">
        Jesli nie chcesz otrzymywac wiadomosci z tej listy, mozesz od razu wypisac ten adres:
        <a href="{{ route('landing.mailing.unsubscribe', ['token' => $subscriber->unsubscribe_token]) }}" style="color:#b87333;text-decoration:underline;">Wypisz mnie</a>
    </div>
</div>
