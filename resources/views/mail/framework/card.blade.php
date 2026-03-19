<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    {{ $preheader !== '' ? $preheader : $theme['footer_note'] }}
</div>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background-color:#f3efe6;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px;width:100%;">
                <tr>
                    <td style="padding-bottom:14px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">
                        {{ $theme['category_label'] }}
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #e7dccd;border-radius:28px;background-color:#fffaf5;padding:0;overflow:hidden;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="padding:24px 28px 20px;background-color:#fffaf5;border-bottom:1px solid #eee2d4;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr>
                                            <td valign="middle" style="width:76px;">
                                                <a href="{{ $theme['service_url'] }}" style="text-decoration:none;">
                                                    <img src="{{ $theme['service_logo_url'] }}" alt="{{ $theme['service_logo_alt'] }}" width="56" height="56" style="display:block;border:0;width:56px;height:56px;border-radius:16px;">
                                                </a>
                                            </td>
                                            <td valign="middle">
                                                <div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;letter-spacing:0.16em;text-transform:uppercase;color:#8e5520;font-weight:700;">
                                                    {{ $theme['service_name'] }}
                                                </div>
                                                <div style="margin-top:6px;font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.15;color:#1c1917;font-weight:700;">
                                                    Komunikacja, która zostaje blisko parafii.
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            @if ($theme['has_parish'])
                                <tr>
                                    <td style="padding:18px 28px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $theme['accent_soft'] }};border:1px solid {{ $theme['accent_soft'] }};border-radius:18px;">
                                            <tr>
                                                <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;">
                                                    <div style="font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:{{ $theme['accent_color'] }};font-weight:700;">
                                                        Kontekst parafialny
                                                    </div>
                                                    <div style="margin-top:6px;font-size:18px;line-height:1.3;color:#1f2937;font-weight:700;">
                                                        {{ $theme['parish_name'] }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

                            <tr>
                                <td style="padding:28px 28px 18px;font-family:Arial,Helvetica,sans-serif;color:#334155;">
                                    {!! $body_html !!}
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:0 28px 24px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #eadfce;">
                                        <tr>
                                            <td style="padding-top:20px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.7;color:#57534e;">
                                                {{ $theme['mobile_note'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top:18px;">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="padding:0 10px 10px 0;">
                                                            <a href="{{ $theme['service_url'] }}" style="display:inline-block;padding:12px 18px;border-radius:999px;background-color:#1c1917;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;">
                                                                Przejdz do uslugi
                                                            </a>
                                                        </td>
                                                        <td style="padding:0 0 10px 0;">
                                                            <a href="{{ $theme['parish_url'] }}" style="display:inline-block;padding:12px 18px;border-radius:999px;background-color:{{ $theme['accent_color'] }};color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;">
                                                                {{ $theme['parish_link_label'] }}
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top:8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;color:#78716c;">
                                                {{ $theme['footer_note'] }}<br>
                                                {{ $theme['service_name'] }} · <a href="{{ $theme['service_url'] }}" style="color:{{ $theme['accent_color'] }};text-decoration:underline;">{{ $theme['service_url'] }}</a>
                                                · <a href="{{ $theme['parish_url'] }}" style="color:{{ $theme['accent_color'] }};text-decoration:underline;">{{ $theme['parish_link_label'] }}</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
