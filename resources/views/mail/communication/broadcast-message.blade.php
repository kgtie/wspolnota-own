<x-mail::message>
# {{ $subjectLine }}

{!! nl2br(e($messageBody)) !!}

@if (filled($senderName) || filled($senderEmail))
<x-mail::panel>
Wiadomosc wyslana przez: {{ $senderName ?: 'Superadmin' }}@if(filled($senderEmail)) ({{ $senderEmail }})@endif
</x-mail::panel>
@endif

Dziekujemy,<br>
{{ config('app.name') }}
</x-mail::message>
