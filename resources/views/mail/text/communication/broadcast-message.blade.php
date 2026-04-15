{{ $subjectLine }}

@if (filled($campaignName))
Kampania: {{ $campaignName }}
@endif

{{ trim($contentText ?: $messageBody) }}

@if (filled($ctaLabel) && filled($ctaUrl))
{{ $ctaLabel }}: {{ $ctaUrl }}
@endif

@if (filled($senderName) || filled($senderEmail))
Nadawca: {{ $senderName ?: 'Zespol Wspolnoty' }}@if (filled($senderEmail)) ({{ $senderEmail }})@endif
@endif
