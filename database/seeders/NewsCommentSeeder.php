<?php

namespace Database\Seeders;

use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsCommentSeeder extends Seeder
{
    public function run(): void
    {
        $posts = NewsPost::query()
            ->with('parish')
            ->where('comments_enabled', true)
            ->whereIn('status', ['published', 'archived'])
            ->whereDoesntHave('comments')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        if ($posts->isEmpty()) {
            $this->command?->warn('Brak wpisow wymagajacych seedowania komentarzy.');

            return;
        }

        $now = now();
        $totalComments = 0;
        $totalRoots = 0;
        $totalReplies = 0;
        $totalHidden = 0;
        $totalSoftDeleted = 0;
        $postCount = 0;
        $rows = [];

        foreach ($posts->groupBy('parish_id') as $parishId => $parishPosts) {
            /** @var Parish|null $parish */
            $parish = $parishPosts->first()?->parish;

            $verifiedParishionerIds = User::query()
                ->where('home_parish_id', $parishId)
                ->where('is_user_verified', true)
                ->whereNotNull('email_verified_at')
                ->pluck('id')
                ->all();

            $adminIds = User::query()
                ->whereHas('managedParishes', fn ($query) => $query
                    ->where('parish_id', $parishId)
                    ->where('parish_user.is_active', true))
                ->pluck('id')
                ->all();

            $authorIds = array_values(array_unique(array_merge($verifiedParishionerIds, $adminIds)));

            if ($authorIds === []) {
                continue;
            }

            $parishComments = 0;
            $parishRoots = 0;
            $parishReplies = 0;
            $parishHidden = 0;
            $parishSoftDeleted = 0;
            $parishPostsWithComments = 0;

            foreach ($parishPosts as $post) {
                $publicationAnchor = $post->published_at ?? $post->created_at ?? $now->copy()->subDay();

                if (! $publicationAnchor instanceof Carbon) {
                    $publicationAnchor = Carbon::parse($publicationAnchor);
                }

                $postStats = $this->seedPostThread(
                    post: $post,
                    publicationAnchor: $publicationAnchor,
                    authorIds: $authorIds,
                    adminIds: $adminIds,
                    now: $now->copy(),
                );

                if ($postStats['total_comments'] === 0) {
                    continue;
                }

                $postCount++;
                $parishPostsWithComments++;
                $parishComments += $postStats['total_comments'];
                $parishRoots += $postStats['roots'];
                $parishReplies += $postStats['replies'];
                $parishHidden += $postStats['hidden'];
                $parishSoftDeleted += $postStats['soft_deleted'];

                $totalComments += $postStats['total_comments'];
                $totalRoots += $postStats['roots'];
                $totalReplies += $postStats['replies'];
                $totalHidden += $postStats['hidden'];
                $totalSoftDeleted += $postStats['soft_deleted'];
            }

            if ($parishPostsWithComments === 0) {
                continue;
            }

            $rows[] = [
                $parish?->short_name ?: $parish?->name ?: 'Parafia #'.$parishId,
                $parishPostsWithComments,
                $parishComments,
                $parishRoots,
                $parishReplies,
                $parishHidden,
                $parishSoftDeleted,
            ];
        }

        $this->command?->info('');
        $this->command?->info('Seeder komentarzy zakonczony.');

        if ($rows !== []) {
            $this->command?->table(
                ['Parafia', 'Wpisy z komentarzami', 'Komentarze', 'Glowne', 'Odpowiedzi', 'Ukryte', 'Soft delete'],
                $rows,
            );
        }

        $this->command?->table(
            ['Metryka', 'Wartosc'],
            [
                ['Wpisy z dosianymi komentarzami', (string) $postCount],
                ['Laczna liczba komentarzy', (string) $totalComments],
                ['Komentarze glowne', (string) $totalRoots],
                ['Odpowiedzi', (string) $totalReplies],
                ['Komentarze ukryte', (string) $totalHidden],
                ['Komentarze soft deleted', (string) $totalSoftDeleted],
            ],
        );
    }

    /**
     * @param  array<int>  $authorIds
     * @param  array<int>  $adminIds
     * @return array{total_comments:int, roots:int, replies:int, hidden:int, soft_deleted:int}
     */
    protected function seedPostThread(
        NewsPost $post,
        Carbon $publicationAnchor,
        array $authorIds,
        array $adminIds,
        Carbon $now
    ): array {
        $rootCount = $this->determineRootCount($post, $publicationAnchor, $now);

        if ($rootCount === 0) {
            return [
                'total_comments' => 0,
                'roots' => 0,
                'replies' => 0,
                'hidden' => 0,
                'soft_deleted' => 0,
            ];
        }

        $stats = [
            'total_comments' => 0,
            'roots' => 0,
            'replies' => 0,
            'hidden' => 0,
            'soft_deleted' => 0,
        ];

        for ($rootIndex = 0; $rootIndex < $rootCount; $rootIndex++) {
            $rootCreatedAt = $this->randomCommentTimestamp($publicationAnchor, $now);
            $rootAuthorId = $this->pickAuthorId($authorIds);
            $rootId = $this->insertComment(
                postId: (int) $post->getKey(),
                userId: $rootAuthorId,
                parentId: null,
                depth: 0,
                body: $this->buildRootCommentBody(),
                createdAt: $rootCreatedAt,
                adminIds: $adminIds,
                stats: $stats,
                allowSoftDelete: true,
            );

            $stats['total_comments']++;
            $stats['roots']++;

            $replyCount = $this->determineReplyCount(depth: 0, rootCreatedAt: $rootCreatedAt, now: $now);

            for ($replyIndex = 0; $replyIndex < $replyCount; $replyIndex++) {
                $replyCreatedAt = $this->replyTimestampAfter($rootCreatedAt, $now);
                $replyAuthorId = $this->pickDifferentAuthorId($authorIds, $rootAuthorId);
                $replyId = $this->insertComment(
                    postId: (int) $post->getKey(),
                    userId: $replyAuthorId,
                    parentId: $rootId,
                    depth: 1,
                    body: $this->buildReplyBody(depth: 1),
                    createdAt: $replyCreatedAt,
                    adminIds: $adminIds,
                    stats: $stats,
                    allowSoftDelete: true,
                );

                $stats['total_comments']++;
                $stats['replies']++;

                $deepReplyCount = $this->determineReplyCount(depth: 1, rootCreatedAt: $replyCreatedAt, now: $now);

                for ($deepIndex = 0; $deepIndex < $deepReplyCount; $deepIndex++) {
                    $deepCreatedAt = $this->replyTimestampAfter($replyCreatedAt, $now);
                    $deepAuthorId = $this->pickDifferentAuthorId($authorIds, $replyAuthorId);

                    $this->insertComment(
                        postId: (int) $post->getKey(),
                        userId: $deepAuthorId,
                        parentId: $replyId,
                        depth: 2,
                        body: $this->buildReplyBody(depth: 2),
                        createdAt: $deepCreatedAt,
                        adminIds: $adminIds,
                        stats: $stats,
                        allowSoftDelete: true,
                    );

                    $stats['total_comments']++;
                    $stats['replies']++;
                }
            }
        }

        return $stats;
    }

    protected function determineRootCount(NewsPost $post, Carbon $publicationAnchor, Carbon $now): int
    {
        if ($post->status === 'archived') {
            return random_int(0, 3);
        }

        return $this->determineRootCountForAnchor($publicationAnchor, $now) + ($post->is_pinned ? random_int(1, 2) : 0);
    }

    protected function determineRootCountForAnchor(Carbon $publicationAnchor, Carbon $now): int
    {
        if ($publicationAnchor->gte($now->copy()->subDays(14))) {
            return random_int(4, 11);
        }

        if ($publicationAnchor->gte($now->copy()->subDays(60))) {
            return random_int(2, 7);
        }

        if ($publicationAnchor->gte($now->copy()->subMonths(6))) {
            return random_int(1, 5);
        }

        return random_int(0, 3);
    }

    protected function determineReplyCount(int $depth, Carbon $rootCreatedAt, Carbon $now): int
    {
        if ($depth >= 2) {
            return 0;
        }

        if ($rootCreatedAt->gte($now->copy()->subDays(21))) {
            return $depth === 0 ? random_int(1, 4) : random_int(0, 2);
        }

        if ($rootCreatedAt->gte($now->copy()->subMonths(3))) {
            return $depth === 0 ? random_int(0, 3) : random_int(0, 2);
        }

        return $depth === 0 ? random_int(0, 2) : random_int(0, 1);
    }

    protected function randomCommentTimestamp(Carbon $publicationAnchor, Carbon $now): Carbon
    {
        $start = $publicationAnchor->copy()->max($now->copy()->subMonths(18));
        $end = $publicationAnchor->copy()->addDays(random_int(1, 25))->min($now);

        if ($end->lte($start)) {
            return $start->copy();
        }

        return Carbon::createFromTimestamp(
            random_int($start->timestamp, $end->timestamp)
        );
    }

    protected function replyTimestampAfter(Carbon $createdAt, Carbon $now): Carbon
    {
        $start = $createdAt->copy()->addMinutes(random_int(8, 180));
        $end = $createdAt->copy()->addDays(random_int(1, 12))->min($now);

        if ($end->lte($start)) {
            return $start->min($now);
        }

        return Carbon::createFromTimestamp(
            random_int($start->timestamp, $end->timestamp)
        );
    }

    /**
     * @param  array<int>  $authorIds
     */
    protected function pickAuthorId(array $authorIds): int
    {
        return $authorIds[array_rand($authorIds)];
    }

    /**
     * @param  array<int>  $authorIds
     */
    protected function pickDifferentAuthorId(array $authorIds, int $currentAuthorId): int
    {
        if (count($authorIds) === 1) {
            return $currentAuthorId;
        }

        $candidateIds = array_values(array_filter(
            $authorIds,
            fn (int $authorId): bool => $authorId !== $currentAuthorId,
        ));

        return $candidateIds[array_rand($candidateIds)];
    }

    /**
     * @param  array<int>  $adminIds
     * @param  array{total_comments:int, roots:int, replies:int, hidden:int, soft_deleted:int}  $stats
     */
    protected function insertComment(
        int $postId,
        int $userId,
        ?int $parentId,
        int $depth,
        string $body,
        Carbon $createdAt,
        array $adminIds,
        array &$stats,
        bool $allowSoftDelete = false
    ): int {
        $isHidden = random_int(1, 100) <= ($depth === 0 ? 8 : 6);
        $hiddenAt = $isHidden ? $createdAt->copy()->addHours(random_int(3, 120)) : null;
        $deletedAt = null;

        if ($allowSoftDelete && ! $isHidden && random_int(1, 100) <= 3) {
            $deletedAt = $createdAt->copy()->addHours(random_int(6, 240));
        }

        if ($hiddenAt && $deletedAt && $deletedAt->lte($hiddenAt)) {
            $deletedAt = $hiddenAt->copy()->addHours(2);
        }

        if ($deletedAt) {
            $stats['soft_deleted']++;
        }

        if ($isHidden) {
            $stats['hidden']++;
        }

        return (int) DB::table('news_comments')->insertGetId([
            'news_post_id' => $postId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'depth' => $depth,
            'body' => $body,
            'is_hidden' => $isHidden,
            'hidden_at' => $hiddenAt,
            'hidden_by_user_id' => $isHidden ? $this->pickModeratorId($adminIds, $userId) : null,
            'created_at' => $createdAt,
            'updated_at' => ($hiddenAt ?? $deletedAt ?? $createdAt)->copy(),
            'deleted_at' => $deletedAt,
        ]);
    }

    /**
     * @param  array<int>  $adminIds
     */
    protected function pickModeratorId(array $adminIds, int $fallbackUserId): int
    {
        if ($adminIds === []) {
            return $fallbackUserId;
        }

        return $adminIds[array_rand($adminIds)];
    }

    protected function buildRootCommentBody(): string
    {
        $fragments = [
            'Dziekujemy za te informacje. Czy mozna prosic o potwierdzenie godzin?',
            'Bardzo dobra inicjatywa. Postaramy sie byc na miejscu i pomoc przy organizacji.',
            'Czy zapisy odbywaja sie tylko w kancelarii, czy rowniez po mszy swietej?',
            'Cieszymy sie, ze parafia wraca do tego wydarzenia. To bardzo potrzebne.',
            'Prosze o doprecyzowanie, czy spotkanie jest otwarte takze dla nowych osob.',
            'Dziekujemy za przypomnienie. Przekaze te informacje rodzinie i sasiadom.',
            'Swietny pomysl. Czy bedzie mozliwosc wlaczenia sie w wolontariat jednorazowo?',
            'Czy przewidziano osobne miejsce dla rodzin z dziecmi?',
            'Bardzo potrzebny komunikat. Dobrze, ze pojawil sie z wyprzedzeniem.',
            'Prosze o informacje, czy wydarzenie odbedzie sie niezaleznie od pogody.',
        ];

        return $fragments[array_rand($fragments)];
    }

    protected function buildReplyBody(int $depth): string
    {
        $levelOne = [
            'Tak, potwierdzamy. Szczegoly sa aktualne i beda jeszcze przypomniane przed wydarzeniem.',
            'Mozna zapisac sie rowniez bezposrednio po mszy. Kancelaria to tylko jedna z opcji.',
            'Jesli pojawia sie zmiany, dopiszemy je jeszcze w kolejnym komunikacie.',
            'Spotkanie jest otwarte, zapraszamy takze osoby nowe i spoza stalej wspolnoty.',
            'Wolontariusze beda potrzebni juz od godzin popoludniowych, ale mozna dolaczyc takze pozniej.',
            'Dziekujemy za gotowosc do pomocy. Takie wsparcie bardzo ulatwia organizacje.',
        ];

        $levelTwo = [
            'Dziekujemy za doprecyzowanie, to rozwiewa wszystkie watpliwosci.',
            'Super, w takim razie zglosimy sie po najblizszej mszy.',
            'To bardzo cenna informacja. Przekaze dalej pozostalim zainteresowanym.',
            'W porzadku, dziekujemy za szybka odpowiedz.',
            'To dla nas wazne, dziekujemy za otwartosc i jasne zasady.',
            'Rozumiem, dziekuje. W takim razie do zobaczenia na miejscu.',
        ];

        $pool = $depth === 1 ? $levelOne : $levelTwo;

        return $pool[array_rand($pool)];
    }
}
