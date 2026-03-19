<div style="font-family:Arial,Helvetica,sans-serif;">
    @if (filled($campaignName))
        <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">{{ $campaignName }}</div>
    @endif

    <h1 style="margin:10px 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:32px;line-height:1.15;color:#1c1917;">
        {{ $subjectLine }}
    </h1>

    @if (filled($heroImageUrl))
        <img src="{{ $heroImageUrl }}" alt="{{ $subjectLine }}" style="display:block;width:100%;max-width:624px;height:auto;border:0;border-radius:20px;margin:0 0 18px;">
    @endif

    @if (filled($contentHtml))
        <div style="font-size:16px;line-height:1.8;color:#475569;">
            {!! $contentHtml !!}
        </div>
    @else
        <div style="font-size:16px;line-height:1.8;color:#475569;">
            {!! nl2br(e($messageBody)) !!}
        </div>
    @endif

    @if (filled($ctaLabel) && filled($ctaUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:22px;">
            <tr>
                <td>
                    <a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 20px;border-radius:999px;background-color:#b87333;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                        {{ $ctaLabel }}
                    </a>
                </td>
            </tr>
        </table>
    @endif

    @if (filled($senderName) || filled($senderEmail))
        <div style="margin-top:22px;padding:15px 18px;border-radius:18px;background-color:#f8f3ec;border:1px solid #eadfce;">
            <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#8e5520;font-weight:700;">Nadawca kampanii</div>
            <div style="margin-top:8px;font-size:15px;line-height:1.7;color:#57534e;">
                {{ $senderName ?: 'Zespol Wspolnoty' }}@if (filled($senderEmail)) · {{ $senderEmail }}@endif
            </div>
        </div>
    @endif
</div>
