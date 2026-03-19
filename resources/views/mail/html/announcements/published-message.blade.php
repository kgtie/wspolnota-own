<div style="font-family:Arial,Helvetica,sans-serif;">
    <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#8e5520;font-weight:700;">Ogloszenia parafialne</div>
    <h1 style="margin:10px 0 14px;font-family:Georgia,'Times New Roman',serif;font-size:32px;line-height:1.15;color:#1c1917;">Nowy zestaw ogloszen jest gotowy.</h1>
    <p style="margin:0 0 14px;font-size:16px;line-height:1.75;color:#475569;">
        W parafii <strong>{{ $parishName }}</strong> opublikowano nowy zestaw ogloszen obowiazujacy od
        <strong>{{ $announcementSet->effective_from?->format('d.m.Y') ?? 'dzisiaj' }}</strong>.
    </p>
    <div style="margin:18px 0;padding:16px 18px;border:1px solid #eadfce;border-radius:18px;background-color:#fffdf9;">
        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#8e5520;font-weight:700;">Tytul zestawu</div>
        <div style="margin-top:8px;font-size:20px;line-height:1.4;color:#111827;font-weight:700;">{{ $announcementSet->title }}</div>
        @if ($announcementSet->summary_ai)
            <p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#57534e;">
                {{ $announcementSet->summary_ai }}
            </p>
        @endif
    </div>
    <p style="margin:0;font-size:15px;line-height:1.75;color:#57534e;">
        Najwazniejsze informacje sa juz dostepne we Wspolnocie i na publicznej stronie parafii.
    </p>
</div>
