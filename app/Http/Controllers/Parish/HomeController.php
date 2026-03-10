<?php

namespace App\Http\Controllers\Parish;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementSet;
use App\Models\Parish;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Parish $subdomain): View
    {
        $parish = $subdomain;

        $sharedViewData = [
            'parish' => $parish,
            'accentColor' => $this->normalizeAccentColor((string) $parish->getSetting('primary_color', '#b87333')),
            'avatarUrl' => $this->resolveParishMediaUrl($parish, 'avatar', 'thumb', 'avatar'),
            'coverImageUrl' => $this->resolveParishMediaUrl($parish, 'cover', 'preview', 'cover_image'),
            'websiteUrl' => $this->normalizeWebsiteUrl($parish->website),
            'addressLines' => collect([
                $parish->street,
                trim(collect([$parish->postal_code, $parish->city])->filter()->implode(' ')),
            ])->filter()->values(),
        ];

        if ($parish->is_active) {
            return view('parish.home.active', [
                ...$sharedViewData,
                'currentAnnouncement' => $this->currentAnnouncement($parish),
                'nextMasses' => $this->nextMasses($parish),
                'latestNews' => $this->latestNews($parish),
            ]);
        }

        return view('parish.home.inactive', [
            ...$sharedViewData,
            'suggestionMailtoUrl' => $this->buildSuggestionMailtoUrl($parish),
        ]);
    }

    private function currentAnnouncement(Parish $parish): ?AnnouncementSet
    {
        return $parish->announcementSets()
            ->published()
            ->current()
            ->with([
                'items' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('position'),
            ])
            ->latest('effective_from')
            ->first();
    }

    private function nextMasses(Parish $parish): Collection
    {
        return $parish->masses()
            ->where('status', 'scheduled')
            ->where('celebration_at', '>=', now())
            ->orderBy('celebration_at')
            ->limit(6)
            ->get();
    }

    private function latestNews(Parish $parish): Collection
    {
        return $parish->newsPosts()
            ->published()
            ->withCount('comments')
            ->orderByDesc('is_pinned')
            ->orderByRaw('COALESCE(published_at, created_at) desc')
            ->limit(4)
            ->get();
    }

    private function resolveParishMediaUrl(
        Parish $parish,
        string $collection,
        string $conversion,
        string $legacyColumn,
    ): ?string {
        $mediaUrl = $parish->getFirstMediaUrl($collection, $conversion);

        if (filled($mediaUrl)) {
            return $mediaUrl;
        }

        $legacyPath = $parish->getAttribute($legacyColumn);

        if (! is_string($legacyPath) || blank($legacyPath)) {
            return null;
        }

        if (Str::startsWith($legacyPath, ['http://', 'https://', '/'])) {
            return $legacyPath;
        }

        return Storage::disk('profiles')->url($legacyPath);
    }

    private function normalizeWebsiteUrl(?string $website): ?string
    {
        if (blank($website)) {
            return null;
        }

        return Str::startsWith($website, ['http://', 'https://'])
            ? $website
            : "https://{$website}";
    }

    private function normalizeAccentColor(string $color): string
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color)
            ? $color
            : '#b87333';
    }

    private function buildSuggestionMailtoUrl(Parish $parish): ?string
    {
        if (blank($parish->email)) {
            return null;
        }

        $subject = rawurlencode("Może warto rozważyć Wspólnotę dla {$parish->short_name}");
        $body = rawurlencode(implode("\n", [
            'Szczęść Boże,',
            '',
            "trafiłem/am na publiczną stronę {$parish->short_name} w usłudze Wspólnota.",
            'Pomyślałem/am, że takie narzędzie mogłoby być pomocne w publikowaniu ogłoszeń, aktualności i informacji o mszach świętych.',
            '',
            'Jeśli będzie Ksiądz zainteresowany, warto przyjrzeć się temu rozwiązaniu.',
            '',
            'Z modlitwą i pozdrowieniami.',
        ]));

        return "mailto:{$parish->email}?subject={$subject}&body={$body}";
    }
}
