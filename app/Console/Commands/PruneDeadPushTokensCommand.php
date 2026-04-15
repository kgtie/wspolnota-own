<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use Illuminate\Console\Command;

class PruneDeadPushTokensCommand extends Command
{
    protected $signature = 'push:prune-dead-tokens
        {--dry-run : Tylko raport, bez usuwania rekordow}
        {--invalid-hours=24 : Po ilu godzinach usuwac tokeny z bledem UNREGISTERED lub INVALID_ARGUMENT}
        {--chunk=250 : Rozmiar paczki przetwarzania}';

    protected $description = 'Usuwa martwe tokeny FCM oznaczone przez backend jako UNREGISTERED albo INVALID_ARGUMENT.';

    public function handle(): int
    {
        $cutoff = now()->subHours(max(1, (int) $this->option('invalid-hours')));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $query = UserDevice::query()
            ->deadToken()
            ->where(function ($query) use ($cutoff): void {
                $query
                    ->where('last_push_error_at', '<=', $cutoff)
                    ->orWhere('updated_at', '<=', $cutoff);
            })
            ->orderBy('id');

        $counters = [
            'matched' => (clone $query)->count(),
            'deleted' => 0,
            'unregistered' => 0,
            'invalid_argument' => 0,
        ];

        $this->info(sprintf(
            'push:prune-dead-tokens | matched=%d | dry_run=%s | cutoff=%s',
            $counters['matched'],
            $dryRun ? 'true' : 'false',
            $cutoff->toDateTimeString(),
        ));

        if ($counters['matched'] === 0) {
            return self::SUCCESS;
        }

        $query->chunkById($chunkSize, function ($devices) use (&$counters, $dryRun): void {
            foreach ($devices as $device) {
                $error = (string) $device->last_push_error;

                if (str_contains($error, 'UNREGISTERED')) {
                    $counters['unregistered']++;
                }

                if (str_contains($error, 'INVALID_ARGUMENT')) {
                    $counters['invalid_argument']++;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '[dry-run] delete device_id=%s user_id=%s platform=%s error=%s',
                        (string) $device->device_id,
                        (string) $device->user_id,
                        (string) $device->platform,
                        $error,
                    ));

                    continue;
                }

                $device->delete();
                $counters['deleted']++;
            }
        });

        $this->info(sprintf(
            'push:prune-dead-tokens | deleted=%d | unregistered=%d | invalid_argument=%d',
            $counters['deleted'],
            $counters['unregistered'],
            $counters['invalid_argument'],
        ));

        return self::SUCCESS;
    }
}
