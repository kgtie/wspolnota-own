<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsPost;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListNewsPosts extends ListRecords
{
    protected static string $resource = NewsPostResource::class;

    protected string $view = 'filament.admin.resources.news-posts.pages.list-news-posts';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nowa aktualnosc')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $baseQuery = $this->getNewsroomBaseQuery();

        return [
            'all' => Tab::make('Wszystkie')
                ->icon('heroicon-o-newspaper')
                ->badge((clone $baseQuery)->count()),
            'draft' => Tab::make('Szkice')
                ->icon('heroicon-o-document-text')
                ->badge((clone $baseQuery)->where('status', 'draft')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'draft')),
            'scheduled' => Tab::make('Zaplanowane')
                ->icon('heroicon-o-clock')
                ->badge((clone $baseQuery)->where('status', 'scheduled')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'scheduled')),
            'published' => Tab::make('Opublikowane')
                ->icon('heroicon-o-check-circle')
                ->badge((clone $baseQuery)->where('status', 'published')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'published')),
            'archived' => Tab::make('Archiwum')
                ->icon('heroicon-o-archive-box')
                ->badge((clone $baseQuery)->where('status', 'archived')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'archived')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }

    /**
     * @return array<int, array{label: string, value: string, description: string, tone: string}>
     */
    public function getNewsroomMetrics(): array
    {
        $baseQuery = $this->getNewsroomBaseQuery();
        $publishedThisMonth = (clone $baseQuery)
            ->where('status', 'published')
            ->whereBetween('published_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $readyQueue = (clone $baseQuery)
            ->whereIn('status', ['draft', 'scheduled'])
            ->count();
        $pinned = (clone $baseQuery)
            ->where('is_pinned', true)
            ->count();
        $updatedRecently = (clone $baseQuery)
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return [
            [
                'label' => 'Kolejka redakcyjna',
                'value' => (string) $readyQueue,
                'description' => 'Szkice i wpisy zaplanowane czekajace na dopracowanie lub publikacje.',
                'tone' => 'warm',
            ],
            [
                'label' => 'Opublikowane',
                'value' => (string) ((clone $baseQuery)->where('status', 'published')->count()),
                'description' => 'Aktualnie widoczne wpisy zyjace juz na stronie parafii.',
                'tone' => 'calm',
            ],
            [
                'label' => 'W tym miesiacu',
                'value' => (string) $publishedThisMonth,
                'description' => 'Liczba materialow opublikowanych od poczatku biezacego miesiaca.',
                'tone' => 'cool',
            ],
            [
                'label' => 'Przypiete / aktywne',
                'value' => (string) $pinned,
                'description' => "Wpisy przypiete oraz {$updatedRecently} rekordow ruszanych w ostatnich 7 dniach.",
                'tone' => 'neutral',
            ],
        ];
    }

    protected function getNewsroomBaseQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        return NewsPost::query()
            ->when(
                filled($tenantId),
                fn (Builder $query) => $query->where('parish_id', $tenantId),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            );
    }
}
