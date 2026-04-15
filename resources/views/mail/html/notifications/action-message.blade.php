<div style="font-family:Arial,Helvetica,sans-serif;">
    @if (filled($eyebrow))
        <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">{{ $eyebrow }}</div>
    @endif
    <h1 style="margin:10px 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;color:#1c1917;">{{ $title }}</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.8;color:#475569;">{{ $intro }}</p>

    @if (! empty($details))
        <div style="margin-bottom:18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
            @foreach ($details as $label => $value)
                <strong>{{ $label }}:</strong> {{ $value }}<br>
            @endforeach
        </div>
    @endif

    @if (! empty($bullets))
        <div style="margin:0 0 18px;padding:16px 18px;border-radius:18px;background-color:#fffdf9;border:1px solid #eadfce;font-size:15px;line-height:1.8;color:#57534e;">
            @foreach ($bullets as $item)
                - {{ $item }}<br>
            @endforeach
        </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
        <tr>
            <td>
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 20px;border-radius:999px;background-color:#b87333;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                    {{ $actionLabel }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;line-height:1.8;color:#57534e;">{{ $outro }}</p>

    @if (filled($secondaryText ?? null))
        <p style="margin:12px 0 0;font-size:14px;line-height:1.8;color:#57534e;">{{ $secondaryText }}</p>
    @endif
</div>
