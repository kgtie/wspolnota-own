<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\Admin\Resources\Masses\MassResource;
use App\Filament\Admin\Resources\NewsComments\NewsCommentResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\MassesStatsWidget;
use App\Filament\Admin\Widgets\OfficeConversationsStatusWidget;
use App\Filament\Admin\Widgets\ParishionersStatsWidget;
use App\Filament\Admin\Widgets\PriestActionQueueWidget;
use App\Filament\Admin\Widgets\UpcomingMassesTableWidget;
use App\Models\Mass;
use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Models\OfficeConversation;
use App\Models\Parish;
use App\Models\User;
use App\Support\Admin\PriestActionQueue;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Panel parafii';

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            ParishionersStatsWidget::class,
            MassesStatsWidget::class,
            OfficeConversationsStatusWidget::class,
            UpcomingMassesTableWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.pages.dashboard')
                    ->viewData([
                        ...$this->getDashboardViewData(),
                        'showHero' => true,
                        'showBody' => false,
                        'includeStyles' => true,
                    ]),
                Grid::make($this->getColumns())
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                        PriestActionQueueWidget::class,
                    ])),
                View::make('filament.admin.pages.dashboard')
                    ->viewData([
                        ...$this->getDashboardViewData(),
                        'showHero' => false,
                        'showBody' => true,
                        'includeStyles' => false,
                    ]),
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDashboardViewData(): array
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        if (! $tenant instanceof Parish) {
            return [
                'parish' => null,
                'heroMetrics' => [],
                'priorityCards' => [],
                'quickActions' => [],
                'areaCards' => [],
            ];
        }

        $tenantId = $tenant->getKey();
        $today = now()->startOfDay();
        $nextFourteenDaysEnd = $today->copy()->addDays(13)->endOfDay();
        $actionQueue = app(PriestActionQueue::class);

        $parishionersQuery = User::query()
            ->where('role', 0)
            ->where('home_parish_id', $tenantId);

        $pendingApprovals = (clone $parishionersQuery)
            ->where('is_user_verified', false)
            ->count();
        $newRegistrations = (clone $parishionersQuery)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $verifiedParishioners = (clone $parishionersQuery)
            ->where('is_user_verified', true)
            ->count();

        $massesQuery = Mass::query()->where('parish_id', $tenantId);
        $todayMasses = (clone $massesQuery)
            ->whereBetween('celebration_at', [$today, $today->copy()->endOfDay()])
            ->count();
        $upcomingWeekMasses = (clone $massesQuery)
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), $nextFourteenDaysEnd])
            ->count();
        $outstandingStipendiumsQuery = (clone $massesQuery)
            ->whereNotNull('stipendium_amount')
            ->whereNull('stipendium_paid_at')
            ->where('status', '!=', 'cancelled');
        $outstandingStipendiumsCount = (clone $outstandingStipendiumsQuery)->count();

        $coverage = $actionQueue->massCoverage($tenant, 14);
        $currentAnnouncements = $actionQueue->currentAnnouncements($tenant);
        $nextAnnouncements = $actionQueue->nextWeekAnnouncements($tenant);

        $officeSnapshot = $this->resolveOfficeSnapshot($tenant, $user instanceof User ? $user : null);
        $newsSnapshot = $this->resolveNewsSnapshot($tenantId);
        $profileSnapshot = $this->resolveProfileSnapshot($tenant);

        return [
            'parish' => [
                'name' => $tenant->name,
                'short_name' => $tenant->short_name ?: $tenant->name,
                'city' => $tenant->city,
                'diocese' => $tenant->diocese,
                'cover_url' => $tenant->cover_url,
                'avatar_url' => $tenant->avatar_url,
                'admins_count' => $tenant->admins()->count(),
                'verified_parishioners' => $verifiedParishioners,
                'profile_completion' => $profileSnapshot['completion_percent'],
                'profile_missing' => $profileSnapshot['missing'],
            ],
            'heroMetrics' => [
                [
                    'label' => 'Parafianie',
                    'value' => number_format((clone $parishionersQuery)->count(), 0, ',', ' '),
                    'description' => $pendingApprovals > 0
                        ? "Do zatwierdzenia: {$pendingApprovals}"
                        : 'Brak zaleglych zatwierdzen',
                    'tone' => $pendingApprovals > 0 ? 'warning' : 'success',
                    'icon' => 'heroicon-o-users',
                ],
                [
                    'label' => 'Msze dzisiaj',
                    'value' => number_format($todayMasses, 0, ',', ' '),
                    'description' => "Nadchodzace 14 dni: {$upcomingWeekMasses}",
                    'tone' => $coverage['missing_days_count'] > 0 ? $coverage['tone'] : 'info',
                    'icon' => 'heroicon-o-calendar-days',
                ],
                [
                    'label' => 'Ogloszenia',
                    'value' => $currentAnnouncements['hero_value'],
                    'description' => $nextAnnouncements['hero_description'],
                    'tone' => $currentAnnouncements['tone'],
                    'icon' => 'heroicon-o-megaphone',
                ],
                [
                    'label' => 'Kancelaria',
                    'value' => $officeSnapshot['hero_value'],
                    'description' => $officeSnapshot['hero_description'],
                    'tone' => $officeSnapshot['tone'],
                    'icon' => 'heroicon-o-chat-bubble-left-right',
                ],
                [
                    'label' => 'Newsroom',
                    'value' => $newsSnapshot['hero_value'],
                    'description' => $newsSnapshot['hero_description'],
                    'tone' => $newsSnapshot['tone'],
                    'icon' => 'heroicon-o-newspaper',
                ],
            ],
            'priorityCards' => $this->resolvePriorityCards(
                pendingApprovals: $pendingApprovals,
                newRegistrations: $newRegistrations,
                currentAnnouncements: $currentAnnouncements,
                nextAnnouncements: $nextAnnouncements,
                coverage: $coverage,
                officeSnapshot: $officeSnapshot,
                newsSnapshot: $newsSnapshot,
                profileSnapshot: $profileSnapshot,
            ),
            'quickActions' => $this->resolveQuickActions($officeSnapshot),
            'areaCards' => [
                [
                    'title' => 'Wspolnota',
                    'tone' => $pendingApprovals > 0 ? 'warning' : 'success',
                    'tone_label' => $pendingApprovals > 0 ? 'uwaga' : 'stabilnie',
                    'status' => $pendingApprovals > 0
                        ? 'Sa sprawy do zatwierdzenia'
                        : 'Ruch parafian jest pod kontrola',
                    'description' => 'To tutaj domyka sie rejestracja wiernych, role administracyjne i status aktywnych kont.',
                    'metrics' => [
                        ['label' => 'Parafianie', 'value' => number_format((clone $parishionersQuery)->count(), 0, ',', ' ')],
                        ['label' => 'Nowe konta 7d', 'value' => number_format($newRegistrations, 0, ',', ' ')],
                        ['label' => 'Czeka na zatwierdzenie', 'value' => number_format($pendingApprovals, 0, ',', ' ')],
                    ],
                    'url' => UserResource::getUrl('index'),
                    'action_label' => 'Otworz parafian',
                ],
                [
                    'title' => 'Liturgia',
                    'tone' => $this->resolveLiturgiaTone($currentAnnouncements['tone'], $nextAnnouncements['tone'], $coverage['tone'], $outstandingStipendiumsCount),
                    'tone_label' => $this->resolveLiturgiaToneLabel($currentAnnouncements['tone'], $nextAnnouncements['tone'], $coverage['tone'], $outstandingStipendiumsCount),
                    'status' => $coverage['missing_days_count'] > 0
                        ? 'Plan tygodnia wymaga dopiecia'
                        : 'Plan liturgiczny jest czytelny',
                    'description' => 'Msze, stypendia i ogloszenia powinny dawac jeden spojny rytm tygodnia.',
                    'metrics' => [
                        ['label' => 'Msze dzisiaj', 'value' => number_format($todayMasses, 0, ',', ' ')],
                        ['label' => 'Brakujace dni 14d', 'value' => number_format($coverage['missing_days_count'], 0, ',', ' ')],
                        ['label' => 'Nierozliczone stypendia', 'value' => number_format($outstandingStipendiumsCount, 0, ',', ' ')],
                    ],
                    'url' => MassResource::getUrl('index'),
                    'action_label' => 'Przejdz do liturgii',
                ],
                [
                    'title' => 'Komunikacja',
                    'tone' => $this->resolveCommunicationTone($officeSnapshot['tone'], $newsSnapshot['tone']),
                    'tone_label' => $this->resolveCommunicationToneLabel($officeSnapshot['tone'], $newsSnapshot['tone']),
                    'status' => $officeSnapshot['enabled']
                        ? 'Parafia ma aktywne kanaly kontaktu'
                        : 'Kancelaria online jest wylaczona',
                    'description' => 'Aktualnosci, komentarze i kancelaria powinny prowadzic ludzi do jednego miejsca odpowiedzi.',
                    'metrics' => [
                        ['label' => 'Kolejka newsroomu', 'value' => number_format($newsSnapshot['ready_queue'], 0, ',', ' ')],
                        ['label' => 'Komentarze 7d', 'value' => number_format($newsSnapshot['recent_comments'], 0, ',', ' ')],
                        ['label' => 'Nieprzeczytane wiadomosci', 'value' => number_format($officeSnapshot['unread_count'], 0, ',', ' ')],
                    ],
                    'url' => $officeSnapshot['enabled'] ? OfficeInbox::getUrl() : NewsPostResource::getUrl('index'),
                    'action_label' => $officeSnapshot['enabled'] ? 'Otworz komunikacje' : 'Przejdz do aktualnosci',
                ],
                [
                    'title' => 'Tozsamosc i ustawienia',
                    'tone' => $profileSnapshot['completion_percent'] < 75 ? 'warning' : 'info',
                    'tone_label' => $profileSnapshot['completion_percent'] < 75 ? 'do dopiecia' : 'rozwoj',
                    'status' => $profileSnapshot['completion_percent'] >= 90
                        ? 'Profil parafii jest dopiety'
                        : 'Wartosci domknac profil i ustawienia',
                    'description' => 'Kontakt, media, notification settings i branding ustawiaja jak parafia wyglada oraz jak odpowiada.',
                    'metrics' => [
                        ['label' => 'Kompletnosc profilu', 'value' => $profileSnapshot['completion_percent'].'%'],
                        ['label' => 'Powiadomienia', 'value' => $tenant->getSetting('notifications_enabled', true) ? 'wlaczone' : 'wylaczone'],
                        ['label' => 'Komentarze', 'value' => $tenant->getSetting('news_comments_enabled', true) ? 'aktywne' : 'wylaczone'],
                    ],
                    'url' => EditParishProfile::getUrl(),
                    'action_label' => 'Zarzadzaj parafia',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolvePriorityCards(
        int $pendingApprovals,
        int $newRegistrations,
        array $currentAnnouncements,
        array $nextAnnouncements,
        array $coverage,
        array $officeSnapshot,
        array $newsSnapshot,
        array $profileSnapshot,
    ): array {
        $cards = [
            [
                'weight' => $this->toneWeight($currentAnnouncements['tone']),
                'tone' => $currentAnnouncements['tone'],
                'eyebrow' => 'Dzisiaj',
                'title' => $currentAnnouncements['priority_title'],
                'body' => $currentAnnouncements['priority_body'],
                'meta' => $currentAnnouncements['meta'],
                'url' => $currentAnnouncements['url'],
                'action_label' => $currentAnnouncements['action_label'],
                'icon' => 'heroicon-o-megaphone',
            ],
            [
                'weight' => $this->toneWeight($nextAnnouncements['tone']),
                'tone' => $nextAnnouncements['tone'],
                'eyebrow' => 'Nastepny tydzien',
                'title' => $nextAnnouncements['priority_title'],
                'body' => $nextAnnouncements['priority_body'],
                'meta' => $nextAnnouncements['meta'],
                'url' => $nextAnnouncements['url'],
                'action_label' => $nextAnnouncements['action_label'],
                'icon' => 'heroicon-o-calendar',
            ],
        ];

        if ($coverage['missing_days_count'] > 0) {
            $cards[] = [
                'weight' => $this->toneWeight($coverage['tone']),
                'tone' => $coverage['tone'],
                'eyebrow' => 'Plan mszalny',
                'title' => 'Brakuje mszy do wpisania na najblizsze 14 dni',
                'body' => 'To ma wywierac presje na dopiecie kalendarza. Jezeli w ciagu 14 dni sa dziury, proboszcz powinien zobaczyc to natychmiast.',
                'meta' => $coverage['summary_inline'],
                'url' => $coverage['url'],
                'action_label' => 'Uzupelnij kalendarz',
                'icon' => 'heroicon-o-clock',
            ];
        }

        if ($pendingApprovals > 0 || $newRegistrations > 0) {
            $cards[] = [
                'weight' => $pendingApprovals > 0 ? 1 : 2,
                'tone' => $pendingApprovals > 0 ? 'warning' : 'info',
                'eyebrow' => 'Wspolnota',
                'title' => $pendingApprovals > 0
                    ? "Czeka {$pendingApprovals} parafian na zatwierdzenie"
                    : 'Ruch parafian wymaga szybkiego przegladu',
                'body' => 'Na liscie parafian domkniesz weryfikacje, kody oraz role administratorow parafii.',
                'meta' => "Nowe rejestracje z 7 dni: {$newRegistrations}",
                'url' => UserResource::getUrl('index'),
                'action_label' => 'Otworz parafian',
                'icon' => 'heroicon-o-user-group',
            ];
        }

        if ($officeSnapshot['enabled'] && ($officeSnapshot['open_count'] > 0 || $officeSnapshot['unread_count'] > 0)) {
            $cards[] = [
                'weight' => $this->toneWeight($officeSnapshot['tone']),
                'tone' => $officeSnapshot['tone'],
                'eyebrow' => 'Kancelaria',
                'title' => $officeSnapshot['unread_count'] > 0
                    ? 'Kancelaria ma nieprzeczytane wiadomosci'
                    : 'Sa otwarte sprawy do domkniecia',
                'body' => 'W panelu kancelarii zobaczysz watki parafian, odpowiesz i zamkniesz sprawy bez wychodzenia z systemu.',
                'meta' => "Otwarte: {$officeSnapshot['open_count']} | Nieprzeczytane: {$officeSnapshot['unread_count']}",
                'url' => OfficeInbox::getUrl(),
                'action_label' => 'Otworz kancelarie',
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ];
        }

        if ($newsSnapshot['ready_queue'] > 0) {
            $cards[] = [
                'weight' => $this->toneWeight($newsSnapshot['tone']),
                'tone' => $newsSnapshot['tone'],
                'eyebrow' => 'Komunikacja',
                'title' => 'Newsroom ma gotowe materialy do dopracowania',
                'body' => 'Szkice, wpisy zaplanowane i komentarze warto przegladac razem, bo to jedna kolejka kontaktu z parafianami.',
                'meta' => "Kolejka redakcyjna: {$newsSnapshot['ready_queue']} | Komentarze 7 dni: {$newsSnapshot['recent_comments']}",
                'url' => NewsPostResource::getUrl('index'),
                'action_label' => 'Przejdz do newsroomu',
                'icon' => 'heroicon-o-newspaper',
            ];
        }

        if ($profileSnapshot['completion_percent'] < 75) {
            $cards[] = [
                'weight' => 2,
                'tone' => 'info',
                'eyebrow' => 'Ustawienia',
                'title' => 'Profil parafii nie jest jeszcze domkniety',
                'body' => 'Dopelnij kontakt, media i konfiguracje. To poprawia odbior uslugi w aplikacji oraz w panelu.',
                'meta' => 'Braki: '.implode(', ', array_slice($profileSnapshot['missing'], 0, 3)),
                'url' => EditParishProfile::getUrl(),
                'action_label' => 'Uzupelnij profil',
                'icon' => 'heroicon-o-building-library',
            ];
        }

        return collect($cards)
            ->sortBy('weight')
            ->take(6)
            ->values()
            ->map(fn (array $card): array => collect($card)->except('weight')->all())
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function resolveQuickActions(array $officeSnapshot): array
    {
        return [
            [
                'label' => 'Zweryfikuj parafian',
                'description' => 'Lista parafian z tabami i akcjami administratorow.',
                'url' => UserResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
                'tone' => 'warning',
            ],
            [
                'label' => 'Dodaj msze',
                'description' => 'Najkrotsza droga do wpisania nowej intencji lub celebracji.',
                'url' => MassResource::getUrl('create'),
                'icon' => 'heroicon-o-plus-circle',
                'tone' => 'info',
            ],
            [
                'label' => 'Ogloszenia',
                'description' => 'Biezace i przyszle zestawy ogloszen z drukiem PDF.',
                'url' => AnnouncementSetResource::getUrl('index'),
                'icon' => 'heroicon-o-megaphone',
                'tone' => 'success',
            ],
            [
                'label' => 'Newsroom',
                'description' => 'Szkice, publikacje i harmonogram aktualnosci parafialnych.',
                'url' => NewsPostResource::getUrl('index'),
                'icon' => 'heroicon-o-newspaper',
                'tone' => 'info',
            ],
            [
                'label' => 'Komentarze',
                'description' => 'Moderacja i odpowiedzi pod wpisami parafialnymi.',
                'url' => NewsCommentResource::getUrl('index'),
                'icon' => 'heroicon-o-chat-bubble-oval-left-ellipsis',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Kancelaria online',
                'description' => $officeSnapshot['enabled']
                    ? 'Rozmowy z parafianami i sprawy kancelaryjne.'
                    : 'Sekcja jest wylaczona, ale mozna ja wlaczyc w ustawieniach.',
                'url' => $officeSnapshot['enabled'] ? OfficeInbox::getUrl() : EditParishProfile::getUrl(),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'tone' => $officeSnapshot['enabled'] ? 'warning' : 'neutral',
            ],
            [
                'label' => 'Zarzadzaj parafia',
                'description' => 'Kontakt, grafiki, branding, powiadomienia i konfiguracja uslugi.',
                'url' => EditParishProfile::getUrl(),
                'icon' => 'heroicon-o-cog-6-tooth',
                'tone' => 'neutral',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveOfficeSnapshot(Parish $tenant, ?User $user): array
    {
        $enabled = (bool) $tenant->getSetting('office_enabled', true);

        if (! $enabled || ! $user instanceof User) {
            return [
                'enabled' => false,
                'open_count' => 0,
                'unread_count' => 0,
                'tone' => 'neutral',
                'hero_value' => 'wylaczona',
                'hero_description' => 'Kancelaria online jest wylaczona',
            ];
        }

        $openQuery = OfficeConversation::query()
            ->where('parish_id', $tenant->getKey())
            ->where('priest_user_id', $user->getKey())
            ->where('status', OfficeConversation::STATUS_OPEN);

        $openCount = (clone $openQuery)->count();
        $unreadCount = (clone $openQuery)
            ->whereHas('messages', fn ($builder) => $builder
                ->whereNull('read_by_priest_at')
                ->where('sender_user_id', '!=', $user->getKey()))
            ->count();

        return [
            'enabled' => true,
            'open_count' => $openCount,
            'unread_count' => $unreadCount,
            'tone' => match (true) {
                $unreadCount > 0 => 'danger',
                $openCount > 0 => 'warning',
                default => 'success',
            },
            'hero_value' => $unreadCount > 0 ? (string) $unreadCount : (string) $openCount,
            'hero_description' => $unreadCount > 0
                ? 'nieprzeczytane wiadomosci'
                : ($openCount > 0 ? 'otwarte sprawy' : 'wszystko domkniete'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveNewsSnapshot(int $tenantId): array
    {
        $newsQuery = NewsPost::query()->where('parish_id', $tenantId);

        $drafts = (clone $newsQuery)->where('status', 'draft')->count();
        $scheduled = (clone $newsQuery)->where('status', 'scheduled')->count();
        $published = (clone $newsQuery)->where('status', 'published')->count();
        $publishedThisMonth = (clone $newsQuery)
            ->where('status', 'published')
            ->whereBetween('published_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $commentsQuery = NewsComment::query()
            ->whereHas('newsPost', fn ($builder) => $builder->where('parish_id', $tenantId));

        $recentComments = (clone $commentsQuery)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $hiddenComments = (clone $commentsQuery)
            ->where('is_hidden', true)
            ->count();

        $readyQueue = $drafts + $scheduled;

        return [
            'drafts' => $drafts,
            'scheduled' => $scheduled,
            'published' => $published,
            'published_this_month' => $publishedThisMonth,
            'recent_comments' => $recentComments,
            'hidden_comments' => $hiddenComments,
            'ready_queue' => $readyQueue,
            'tone' => match (true) {
                $readyQueue >= 6 => 'warning',
                $readyQueue > 0 => 'info',
                default => 'success',
            },
            'hero_value' => (string) $readyQueue,
            'hero_description' => $readyQueue > 0
                ? "materialow w kolejce | komentarze 7d: {$recentComments}"
                : "opublikowane: {$published} | komentarze 7d: {$recentComments}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveProfileSnapshot(Parish $tenant): array
    {
        $checks = [
            'email parafii' => filled($tenant->email),
            'telefon' => filled($tenant->phone),
            'strona WWW' => filled($tenant->website),
            'ulica' => filled($tenant->street),
            'kod pocztowy' => filled($tenant->postal_code),
            'miasto' => filled($tenant->city),
            'diecezja' => filled($tenant->diocese),
            'logo' => $tenant->hasMedia('avatar'),
            'cover' => $tenant->hasMedia('cover'),
        ];

        $missing = collect($checks)
            ->filter(fn (bool $isComplete): bool => ! $isComplete)
            ->keys()
            ->values()
            ->all();

        $completionPercent = (int) round(((count($checks) - count($missing)) / max(count($checks), 1)) * 100);

        return [
            'completion_percent' => $completionPercent,
            'missing' => $missing,
        ];
    }

    protected function resolveLiturgiaTone(string $currentTone, string $nextTone, string $coverageTone, int $outstandingStipendiumsCount): string
    {
        $tones = [$currentTone, $nextTone, $coverageTone];

        if (in_array('danger', $tones, true)) {
            return 'danger';
        }

        if (in_array('warning', $tones, true) || ($outstandingStipendiumsCount > 0)) {
            return 'warning';
        }

        return 'success';
    }

    protected function resolveCommunicationTone(string $officeTone, string $newsTone): string
    {
        $tones = [$officeTone, $newsTone];

        if (in_array('danger', $tones, true)) {
            return 'danger';
        }

        if (in_array('warning', $tones, true)) {
            return 'warning';
        }

        if (in_array('info', $tones, true)) {
            return 'info';
        }

        return 'success';
    }

    protected function resolveLiturgiaToneLabel(string $currentTone, string $nextTone, string $coverageTone, int $outstandingStipendiumsCount): string
    {
        return match ($this->resolveLiturgiaTone($currentTone, $nextTone, $coverageTone, $outstandingStipendiumsCount)) {
            'danger' => 'krytyczne',
            'warning' => 'uwaga',
            default => 'stabilnie',
        };
    }

    protected function resolveCommunicationToneLabel(string $officeTone, string $newsTone): string
    {
        return match ($this->resolveCommunicationTone($officeTone, $newsTone)) {
            'danger' => 'pilne',
            'warning' => 'uwaga',
            'info' => 'w ruchu',
            default => 'spokojnie',
        };
    }

    protected function toneWeight(string $tone): int
    {
        return match ($tone) {
            'danger' => 0,
            'warning' => 1,
            'info', 'primary' => 2,
            'neutral', 'gray' => 3,
            default => 4,
        };
    }
}
