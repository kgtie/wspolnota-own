<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class FailedJobsCenter extends Page
{
    protected static ?string $title = 'Failed Jobs';

    protected static ?string $navigationLabel = 'Failed Jobs';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static string | \UnitEnum | null $navigationGroup = 'System i diagnostyka';

    protected static ?int $navigationSort = 30;

    protected ?string $subheading = 'Pelny podglad nieudanych jobow kolejki z retry, forget i flush.';

    protected string $view = 'filament.superadmin.pages.failed-jobs-center';

    protected ?string $pollingInterval = '20s';

    public string $queueFilter = '';

    public string $search = '';

    public static function getNavigationBadge(): ?string
    {
        if (! Schema::hasTable('failed_jobs')) {
            return null;
        }

        $count = DB::table('failed_jobs')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'danger' : 'success';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_all_failed_jobs')
                ->label('Retry all')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->retryAllFailedJobs()),
            Action::make('flush_failed_jobs')
                ->label('Flush all')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->flushFailedJobs()),
        ];
    }

    public function getFailedJobStatsProperty(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        $rows = collect($this->failedJobs);

        return [
            ['label' => 'Wszystkie failed jobs', 'value' => DB::table('failed_jobs')->count()],
            ['label' => 'Mail jobs', 'value' => $rows->where('kind', 'mail')->count()],
            ['label' => 'Push jobs', 'value' => $rows->where('kind', 'push')->count()],
            ['label' => 'Pozostale', 'value' => $rows->where('kind', 'other')->count()],
        ];
    }

    public function getQueueOptionsProperty(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->select('queue')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue', 'queue')
            ->all();
    }

    public function getFailedJobsProperty(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->when($this->queueFilter !== '', fn ($query) => $query->where('queue', $this->queueFilter))
            ->orderByDesc('failed_at')
            ->limit(120)
            ->get()
            ->map(fn (stdClass $row): array => $this->parseFailedJob($row))
            ->filter(function (array $row): bool {
                if ($this->search === '') {
                    return true;
                }

                $needle = mb_strtolower($this->search);

                return str_contains(mb_strtolower($row['display_name']), $needle)
                    || str_contains(mb_strtolower($row['exception_headline']), $needle)
                    || str_contains(mb_strtolower($row['queue']), $needle)
                    || str_contains(mb_strtolower($row['kind']), $needle);
            })
            ->values()
            ->all();
    }

    public function retryFailedJob(int $jobId): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Notification::make()
                ->warning()
                ->title('Tabela failed_jobs nie istnieje.')
                ->send();

            return;
        }

        Artisan::call('queue:retry', ['id' => [$jobId]]);

        Notification::make()
            ->success()
            ->title("Ponowiono failed job #{$jobId}.")
            ->send();
    }

    public function forgetFailedJob(int $jobId): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Notification::make()
                ->warning()
                ->title('Tabela failed_jobs nie istnieje.')
                ->send();

            return;
        }

        Artisan::call('queue:forget', ['id' => $jobId]);

        Notification::make()
            ->success()
            ->title("Usunieto failed job #{$jobId}.")
            ->send();
    }

    public function retryAllFailedJobs(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Notification::make()
                ->warning()
                ->title('Tabela failed_jobs nie istnieje.')
                ->send();

            return;
        }

        $ids = DB::table('failed_jobs')->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($ids === []) {
            Notification::make()
                ->warning()
                ->title('Brak failed jobs do retry.')
                ->send();

            return;
        }

        Artisan::call('queue:retry', ['id' => $ids]);

        Notification::make()
            ->success()
            ->title('Zakolejkowano retry dla wszystkich failed jobs.')
            ->body('Liczba jobow: '.count($ids))
            ->send();
    }

    public function flushFailedJobs(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Notification::make()
                ->warning()
                ->title('Tabela failed_jobs nie istnieje.')
                ->send();

            return;
        }

        Artisan::call('queue:flush');

        Notification::make()
            ->success()
            ->title('Wyczyszczono failed jobs.')
            ->send();
    }

    private function parseFailedJob(stdClass $row): array
    {
        $payload = json_decode((string) $row->payload, true);
        $displayName = is_array($payload) ? (string) ($payload['displayName'] ?? 'unknown') : 'unknown';

        return [
            'id' => (int) $row->id,
            'kind' => $this->resolveKind($displayName),
            'display_name' => $displayName,
            'queue' => (string) $row->queue,
            'connection' => (string) $row->connection,
            'failed_at' => is_string($row->failed_at) ? $row->failed_at : (string) $row->failed_at,
            'exception_headline' => str((string) $row->exception)->before("\n")->limit(220)->toString(),
        ];
    }

    private function resolveKind(string $displayName): string
    {
        return match (true) {
            str_contains($displayName, 'Mail'),
            str_contains($displayName, 'Mailable'),
            str_contains($displayName, 'CommunicationBroadcastMessage') => 'mail',
            str_contains($displayName, 'Push'),
            str_contains($displayName, 'SendManualPushToDeviceJob') => 'push',
            default => 'other',
        };
    }
}
