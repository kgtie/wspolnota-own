{{ $title }}

{{ $intro }}
@if (! empty($details))
@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@endif
@if (! empty($bullets))
@foreach ($bullets as $item)
- {{ $item }}
@endforeach
@endif

{{ $actionLabel }}: {{ $actionUrl }}

{{ $outro }}
@if (filled($secondaryText ?? null))

{{ $secondaryText }}
@endif
