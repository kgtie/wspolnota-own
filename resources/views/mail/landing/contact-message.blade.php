<x-mail::message>
# Nowa wiadomość z formularza kontaktowego

**Nadawca:** {{ $name }}  
**Email:** {{ $email }}

@if (filled($parish))
**Parafia:** {{ $parish }}
@endif

@if (filled($phone))
**Telefon:** {{ $phone }}
@endif

**Temat:** {{ $subjectLine }}

<x-mail::panel>
{!! nl2br(e($messageBody)) !!}
</x-mail::panel>

Dziękujemy,<br>
{{ config('app.name') }}
</x-mail::message>
