<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <title>Ogloszenia parafialne</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
            margin: 28px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 6px;
        }

        h2 {
            font-size: 14px;
            margin: 0;
            color: #4b5563;
            font-weight: normal;
        }

        .meta {
            margin-top: 8px;
            margin-bottom: 16px;
            color: #4b5563;
        }

        .lead {
            margin: 12px 0 16px;
        }

        ol {
            margin: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 10px;
        }

        .item-title {
            font-weight: 700;
            margin-right: 4px;
        }

        .important {
            font-weight: 700;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #d1d5db;
            padding-top: 12px;
            color: #374151;
        }

        .generated {
            margin-top: 24px;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <h1>{{ $set->title }}</h1>
    <h2>{{ $set->parish?->name ?? 'Parafia' }}</h2>

    <div class="meta">
        <strong>Okres:</strong>
        {{ $set->effective_from?->format('d.m.Y') ?? 'brak' }}
        @if($set->effective_to)
            - {{ $set->effective_to->format('d.m.Y') }}
        @endif
        @if($set->week_label)
            <br>
            <strong>Opis:</strong> {{ $set->week_label }}
        @endif
    </div>

    @if($set->lead)
        <div class="lead">{!! nl2br(e($set->lead)) !!}</div>
    @endif

    <ol>
        @foreach($items as $item)
            <li class="{{ $item->is_important ? 'important' : '' }}">
                @if($item->title)
                    <span class="item-title">{{ $item->title }}:</span>
                @endif
                {!! nl2br(e($item->content)) !!}
            </li>
        @endforeach
    </ol>

    @if($set->footer_notes)
        <div class="footer">{!! nl2br(e($set->footer_notes)) !!}</div>
    @endif

    <div class="generated">
        Wygenerowano: {{ $generatedAt->format('d.m.Y H:i') }}
    </div>
</body>

</html>
