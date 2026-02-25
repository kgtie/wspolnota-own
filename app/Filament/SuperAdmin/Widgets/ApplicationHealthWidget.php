<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ApplicationHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Kondycja aplikacji';

    protected ?string $description = 'Szybki podglad najwazniejszych wskaznikow platformy.';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $databaseState = $this->isDatabaseHealthy() ? 'OK' : 'BLAD';
        $databaseColor = $databaseState === 'OK' ? 'success' : 'danger';

        $settingsCount = $this->tableCount('settings');
        $activityCount24h = $this->countActivityLogsLast24Hours();
        $jobsCount = $this->tableCount(config('queue.connections.database.table', 'jobs'));
        $failedJobsCount = $this->tableCount(config('queue.failed.table', 'failed_jobs'));
        $lastSettingsUpdate = $this->latestSettingsUpdate();

        return [
            Stat::make('Baza danych', $databaseState)
                ->description('Polaczenie z baza danych')
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color($databaseColor),

            Stat::make('Klucze settings', number_format($settingsCount, 0, ',', ' '))
                ->description($lastSettingsUpdate ? "Ostatnia zmiana: {$lastSettingsUpdate}" : 'Brak wpisow')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color($settingsCount > 0 ? 'primary' : 'warning'),

            Stat::make('Logi 24h', number_format($activityCount24h, 0, ',', ' '))
                ->description('Nowe wpisy w activity_log')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color($activityCount24h > 0 ? 'info' : 'gray'),

            Stat::make('Kolejka (jobs)', number_format($jobsCount, 0, ',', ' '))
                ->description('Oczekujace zadania')
                ->descriptionIcon('heroicon-o-queue-list')
                ->color($jobsCount > 0 ? 'warning' : 'success'),

            Stat::make('Kolejka (failed)', number_format($failedJobsCount, 0, ',', ' '))
                ->description('Bledne zadania')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($failedJobsCount > 0 ? 'danger' : 'success'),

            Stat::make('Cache settings', config('settings.cache.enabled') ? 'WLACZONY' : 'WYLACZONY')
                ->description('Konfiguracja settings cache')
                ->descriptionIcon('heroicon-o-bolt')
                ->color(config('settings.cache.enabled') ? 'success' : 'gray'),
        ];
    }

    protected function isDatabaseHealthy(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function tableCount(string $table): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    protected function countActivityLogsLast24Hours(): int
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        if (! $this->tableExists($tableName)) {
            return 0;
        }

        return (int) DB::table($tableName)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    protected function latestSettingsUpdate(): ?string
    {
        if (! $this->tableExists('settings')) {
            return null;
        }

        $updatedAt = DB::table('settings')->max('updated_at');

        if (! $updatedAt) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($updatedAt)->format('d.m.Y H:i');
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
