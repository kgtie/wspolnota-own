<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 6px 0;
        }

        .meta {
            color: #555;
            margin-bottom: 14px;
        }

        .item {
            margin: 0 0 10px 0;
        }

        .important {
            font-weight: 700;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 12px 0;
        }
    </style>
</head>

<body>
    <h1>Ogłoszenia parafialne</h1>

    <div class="meta">
        <div><strong>Parafia:</strong> {{ $parishName }}</div>
        <div><strong>Zestaw:</strong> {{ $set->title }}</div>
        <div><strong>Okres:</strong> {{ $set->valid_from?->format('d.m.Y') }} –
            {{ $set->valid_until?->format('d.m.Y') }}
        </div>
        @if($set->ai_summary)
            <hr>
            <div><strong>Streszczenie (AI):</strong> {{ $set->ai_summary }}</div>
        @endif
        <hr>
    </div>

    @foreach($announcements as $a)
        <div class="item {{ $a->is_highlighted ? 'important' : '' }}">
            {!! $a->content !!}
        </div>
    @endforeach
</body>

</html>