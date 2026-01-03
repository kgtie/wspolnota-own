<x-mail::message>

    <h1 class="header-text body-font"
        style="margin: 0 0 20px 0; font-size: 28px; line-height: 1.3; font-weight: 600; color: #1F2937;">
        Szczęść Boże! 👋
    </h1>

    <p class="body-font" style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        Przekazujemy Ci wiadomość z Twojej parafii.
    </p>

    <p class="body-font" style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        {{ $subjectLine }}
    </p>

    <p class="body-font" style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        {!! nl2br(e($bodyText)) !!}
    </p>

    <hr />

    <p class="body-font" style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4B5563;">
        Msza: {{ \Illuminate\Support\Carbon::parse($mass->start_time)->format('d.m.Y H:i') }}<br>
        Miejsce: {{ $mass->location }}
    </p>
</x-mail::message>