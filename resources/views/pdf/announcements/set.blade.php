<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Ogłoszenia parafialne</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 11mm 11mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .page {
            min-height: 100%;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d6d3d1;
        }

        .brand-cell,
        .service-cell,
        .header-copy {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-cell,
        .service-cell {
            width: 66px;
        }

        .logo-box {
            width: 54px;
            height: 54px;
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            background: #fffdf8;
            text-align: center;
            vertical-align: middle;
        }

        .logo-box img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 14px;
        }

        .logo-fallback {
            display: inline-block;
            width: 54px;
            line-height: 54px;
            font-size: 8px;
            color: #a8a29e;
        }

        .header-copy {
            padding: 0 8px;
        }

        .eyebrow {
            margin: 0 0 4px;
            font-size: 8px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #b45309;
        }

        .title {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }

        .subtitle {
            margin: 0;
            font-size: 10px;
            color: #4b5563;
            line-height: 1.35;
        }

        .lead-box,
        .closing-box {
            margin-bottom: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #fafaf9;
            border: 1px solid #e7e5e4;
            color: #374151;
            font-size: 9px;
            line-height: 1.45;
            white-space: pre-line;
        }

        .lead-box strong,
        .closing-box strong {
            display: block;
            margin-bottom: 4px;
            color: #111827;
        }

        .items {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .item {
            margin-bottom: 7px;
            padding: 8px 10px;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            background: #ffffff;
        }

        .item-row {
            display: table;
            width: 100%;
        }

        .item.important {
            border-color: #fdba74;
            background: #fff7ed;
        }

        .item-marker-cell,
        .item-body {
            display: table-cell;
            vertical-align: top;
        }

        .item-marker-cell {
            width: 28px;
            padding-right: 10px;
        }

        .item-marker {
            width: 22px;
            height: 22px;
        }

        .item-marker svg {
            display: block;
            width: 22px;
            height: 22px;
        }

        .item-title {
            margin: 0 0 4px;
            font-size: 10px;
            font-weight: 700;
            color: #111827;
        }

        .item-content {
            margin: 0;
            font-size: 9px;
            color: #374151;
            line-height: 1.45;
            white-space: pre-line;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #d6d3d1;
            font-size: 8px;
            line-height: 1.45;
            color: #6b7280;
        }

        .footer strong {
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="brand-cell">
                <div class="logo-box">
                    @if ($parish_logo_data_uri)
                        <img src="{{ $parish_logo_data_uri }}" alt="Logo parafii">
                    @else
                        <span class="logo-fallback">PARAFIA</span>
                    @endif
                </div>
            </div>

            <div class="header-copy">
                <p class="eyebrow">Wydruk parafialny</p>
                <h1 class="title">{{ $set->title }}</h1>
                <p class="subtitle">
                    {{ $set->parish?->name ?? 'Parafia' }}<br>
                    Okres obowiazywania:
                    {{ $set->effective_from?->format('d.m.Y') ?? 'brak' }}
                    @if ($set->effective_to)
                        - {{ $set->effective_to->format('d.m.Y') }}
                    @endif
                </p>
            </div>

            <div class="service-cell">
                <div class="logo-box">
                    <img src="{{ $service_logo_data_uri }}" alt="Logo Wspólnoty">
                </div>
            </div>
        </div>

        @if ($set->lead)
            <div class="lead-box">
                <strong>Wprowadzenie</strong>
                {{ $set->lead }}
            </div>
        @endif

        <ol class="items">
            @foreach ($items as $item)
                <li class="item{{ $item->is_important ? ' important' : '' }}">
                    <div class="item-row">
                        <div class="item-marker-cell">
                            <span class="item-marker">
                                <svg viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <circle
                                        cx="11"
                                        cy="11"
                                        r="11"
                                        fill="{{ $item->is_important ? '#f59e0b' : '#f3f4f6' }}"
                                    />
                                    <text
                                        x="11"
                                        y="11.8"
                                        text-anchor="middle"
                                        font-size="9"
                                        font-weight="700"
                                        font-family="DejaVu Sans, sans-serif"
                                        fill="{{ $item->is_important ? '#ffffff' : '#4b5563' }}"
                                    >{{ $loop->iteration }}</text>
                                </svg>
                            </span>
                        </div>

                        <div class="item-body">
                            @if ($item->title)
                                <h2 class="item-title">{{ $item->title }}</h2>
                            @endif
                            <p class="item-content">{{ $item->content }}</p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        @if ($set->footer_notes)
            <div class="closing-box">
                <strong>Slowo koncowe</strong>
                {{ $set->footer_notes }}
            </div>
        @endif

        <div class="footer">
            <strong>Wygenerowano:</strong> {{ $generatedAt->format('d.m.Y H:i') }}<br>
            Usługa Wspólnota jest dostępna w App Store oraz Google Play.
        </div>
    </div>
</body>
</html>
