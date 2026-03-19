<div style="font-family:Arial,Helvetica,sans-serif;">
    @if (filled($eyebrow))
        <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">{{ $eyebrow }}</div>
    @endif
    <h1 style="margin:10px 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">{{ $title }}</h1>
    <p style="margin:0 0 14px;font-size:16px;line-height:1.8;color:#475569;">{{ $intro }}</p>
    @if (! empty($details))
        <div style="margin:0 0 14px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
            @foreach ($details as $label => $value)
                <strong>{{ $label }}:</strong> {{ $value }}<br>
            @endforeach
        </div>
    @endif
    @if (filled($body ?? null))
        <div style="padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
            {{ $body }}
        </div>
    @endif
    @if (! empty($bullets))
        <div style="margin-top:14px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
            @foreach ($bullets as $item)
                - {{ $item }}<br>
            @endforeach
        </div>
    @endif
    @if (filled($outro ?? null))
        <p style="margin:14px 0 0;font-size:14px;line-height:1.8;color:#57534e;">{{ $outro }}</p>
    @endif
</div>
