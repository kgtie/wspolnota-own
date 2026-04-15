{{ $title }}

{{ $intro }}
@if (! empty($details))
@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@endif
@if (filled($body ?? null))

{{ $body }}
@endif
@if (! empty($bullets))
@foreach ($bullets as $item)
- {{ $item }}
@endforeach
@endif
@if (filled($outro ?? null))

{{ $outro }}
@endif
