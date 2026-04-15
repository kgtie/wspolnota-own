<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Pages;

use App\Filament\SuperAdmin\Pages\SystemHealth;
use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Widgets\ActivityLogOverviewWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Lista logow jest glownym narzedziem triage dla superadmina.
 * Dostaje zakladki tematyczne, cleanup i szybkie przejscie do globalnych metryk.
 */
class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_system_health')
                ->label('Globalne metryki')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(SystemHealth::getUrl()),

            Action::make('delete_old_logs')
                ->label('Usun stare logi')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->schema([
                    TextInput::make('days')
                        ->label('Starsze niz (dni)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->default(90),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $days = (int) ($data['days'] ?? 90);
                    $deletedCount = Activity::query()
                        ->where('created_at', '<', now()->subDays($days))
                        ->delete();

                    Notification::make()
                        ->success()
                        ->title('Usunieto stare logi.')
                        ->body("Usunieto {$deletedCount} rekordow starszych niz {$days} dni.")
                        ->send();
                }),

            Action::make('delete_all_logs')
                ->label('Wyczysc wszystkie logi')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $deletedCount = Activity::query()->delete();

                    Notification::make()
                        ->success()
                        ->title('Wyczyszczono activity_log.')
                        ->body("Usunięto łącznie {$deletedCount} rekordów.")
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityLogOverviewWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $baseQuery = $this->getActivityBaseQuery();

        return [
            'all' => Tab::make('Wszystkie')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge((clone $baseQuery)->count()),

            'security' => Tab::make('Bezpieczenstwo')
                ->icon('heroicon-o-shield-exclamation')
                ->badge(ActivityLogResource::applySecurityScope(clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applySecurityScope($query)),

            'api_auth' => Tab::make('API auth')
                ->icon('heroicon-o-key')
                ->badge(ActivityLogResource::applyLogNamesScope(clone $baseQuery, ['api-auth'])->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applyLogNamesScope($query, ['api-auth'])),

            'profile' => Tab::make('Konta i profil')
                ->icon('heroicon-o-user')
                ->badge(ActivityLogResource::applyLogNamesScope(clone $baseQuery, [
                    'api-profile',
                    'admin-user-management',
                    'superadmin-user-management',
                    'parish-admin-management',
                ])->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applyLogNamesScope($query, [
                    'api-profile',
                    'admin-user-management',
                    'superadmin-user-management',
                    'parish-admin-management',
                ])),

            'approvals' => Tab::make('Approval flow')
                ->icon('heroicon-o-identification')
                ->badge(ActivityLogResource::applyLogNamesScope(clone $baseQuery, ['api-parish-approvals'])->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applyLogNamesScope($query, ['api-parish-approvals'])),

            'office' => Tab::make('Kancelaria')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->badge(ActivityLogResource::applyLogNamesScope(clone $baseQuery, ['api-office', 'office-conversations'])->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applyLogNamesScope($query, ['api-office', 'office-conversations'])),

            'failures' => Tab::make('Niepowodzenia')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(ActivityLogResource::applyFailureScope(clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => ActivityLogResource::applyFailureScope($query)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }

    protected function getActivityBaseQuery(): Builder
    {
        return Activity::query()->with(['causer', 'subject'])->orderByDesc('created_at');
    }
}
