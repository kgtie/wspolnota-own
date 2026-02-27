<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ParishionersStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Parafianie';

    protected ?string $description = 'Rejestracje i weryfikacje z ostatnich 7 dni.';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $days = 7;

        $baseQuery = User::query()
            ->where('role', 0)
            ->where('home_parish_id', $tenant->getKey());

        $totalParishioners = (clone $baseQuery)->count();
        $newRegistrations = (clone $baseQuery)->where('created_at', '>=', now()->subDays($days))->count();
        $emailVerifications = (clone $baseQuery)
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '>=', now()->subDays($days))
            ->count();
        $codeVerifications = (clone $baseQuery)
            ->where('is_user_verified', true)
            ->whereNotNull('user_verified_at')
            ->where('user_verified_at', '>=', now()->subDays($days))
            ->count();
        $pendingApproval = (clone $baseQuery)->where('is_user_verified', false)->count();
        $usersUrl = UserResource::getUrl('index');

        return [
            Stat::make('Parafianie ogółem', number_format($totalParishioners, 0, ',', ' '))
                ->description("Oczekuje na zatwierdzenie: {$pendingApproval}")
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary')
                ->url($usersUrl),

            Stat::make("Nowe rejestracje ({$days} dni)", number_format($newRegistrations, 0, ',', ' '))
                ->description('Nowo utworzone konta parafian')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info')
                ->chart($this->buildDailyTrend(clone $baseQuery, 'created_at', $days))
                ->url($usersUrl),

            Stat::make("Zweryfikowany email ({$days} dni)", number_format($emailVerifications, 0, ',', ' '))
                ->description('Potwierdzenia adresu email')
                ->descriptionIcon('heroicon-o-envelope-open')
                ->color('success')
                ->chart($this->buildDailyTrend(clone $baseQuery, 'email_verified_at', $days))
                ->url($usersUrl),

            Stat::make("Zatwierdzeni kodem ({$days} dni)", number_format($codeVerifications, 0, ',', ' '))
                ->description('Weryfikacje wykonane przez proboszcza')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('success')
                ->chart($this->buildDailyTrend(clone $baseQuery, 'user_verified_at', $days))
                ->url($usersUrl),
        ];
    }

    protected function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    /**
     * @return array<float>
     */
    protected function buildDailyTrend(Builder $query, string $column, int $days = 7): array
    {
        $trend = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $start = now()->subDays($offset)->startOfDay();
            $end = (clone $start)->endOfDay();

            $trend[] = (float) (clone $query)->whereBetween($column, [$start, $end])->count();
        }

        return $trend;
    }
}
