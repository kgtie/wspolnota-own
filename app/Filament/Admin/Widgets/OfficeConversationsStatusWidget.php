<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\OfficeInbox;
use App\Models\OfficeConversation;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OfficeConversationsStatusWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Kancelaria online';

    protected ?string $description = 'Pilnuje, czy otwarte sprawy petentow sa domykane na biezaco.';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return false;
        }

        return (bool) $tenant->getSetting('office_enabled', true);
    }

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        if (! $tenant || ! $user) {
            return [];
        }

        $openConversationsQuery = OfficeConversation::query()
            ->where('parish_id', $tenant->getKey())
            ->where('priest_user_id', $user->getKey())
            ->where('status', OfficeConversation::STATUS_OPEN);

        $openCount = (clone $openConversationsQuery)->count();

        $unreadCount = (clone $openConversationsQuery)
            ->whereHas('messages', fn ($query) => $query
                ->whereNull('read_by_priest_at')
                ->where('sender_user_id', '!=', $user->getKey()))
            ->count();

        if ($openCount > 0) {
            return [
                Stat::make('Otwarte konwersacje', number_format($openCount, 0, ',', ' '))
                    ->description('Warto domknac sprawy petentow i zamknac konwersacje. Nieprzeczytane watki: '.$unreadCount)
                    ->descriptionIcon('heroicon-o-exclamation-circle')
                    ->color($openCount >= 15 ? 'danger' : 'warning')
                    ->url(OfficeInbox::getUrl()),
            ];
        }

        return [
            Stat::make('Otwarte konwersacje', '0')
                ->description('Brak otwartych spraw. Wszystkie konwersacje sa domkniete.')
                ->descriptionIcon('heroicon-o-face-smile')
                ->color('success')
                ->url(OfficeInbox::getUrl()),
        ];
    }
}
