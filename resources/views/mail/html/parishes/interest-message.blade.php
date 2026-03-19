<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Lead parafialny</div>
    <h1 style="margin:10px 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">Nowe zainteresowanie uruchomieniem uslugi.</h1>
    <div style="padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#475569;">
        <strong>Parafia:</strong> {{ $parish->name }}<br>
        <strong>Krotka nazwa:</strong> {{ $parish->short_name }}<br>
        <strong>Slug:</strong> {{ $parish->slug }}<br>
        <strong>Miejscowosc:</strong> {{ $parish->city }}<br>
        <strong>Data zgloszenia:</strong> {{ $requestedAt->translatedFormat('j F Y, H:i') }}<br>
        @if (filled($parish->email))
            <strong>Email parafii:</strong> {{ $parish->email }}<br>
        @endif
        @if (filled($parish->phone))
            <strong>Telefon parafii:</strong> {{ $parish->phone }}<br>
        @endif
        @if (filled($parish->website))
            <strong>Strona WWW:</strong> {{ $parish->website }}<br>
        @endif
        <strong>Publiczny adres strony:</strong> {{ $publicUrl }}<br>
        @if (filled($requesterIp))
            <strong>IP zgloszenia:</strong> {{ $requesterIp }}<br>
        @endif
        @if (filled($userAgent))
            <strong>User Agent:</strong> {{ $userAgent }}
        @endif
    </div>
</div>
