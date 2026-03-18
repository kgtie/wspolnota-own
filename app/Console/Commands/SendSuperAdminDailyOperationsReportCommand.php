<?php

namespace App\Console\Commands;

use App\Mail\SuperAdminDailyOperationsReportMessage;
use App\Support\Reports\SuperAdminDailyOperationsReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSuperAdminDailyOperationsReportCommand extends Command
{
    protected $signature = 'superadmin:send-daily-operations-report {--date= : Data raportu w formacie YYYY-MM-DD. Domyslnie wczoraj.}';

    protected $description = 'Wysyla szczegolowy dobowy raport operacyjny do glownego superadministratora.';

    public function handle(SuperAdminDailyOperationsReportBuilder $builder): int
    {
        $dateOption = $this->option('date');

        try {
            $date = $dateOption
                ? CarbonImmutable::createFromFormat('Y-m-d', (string) $dateOption, config('app.timezone'))
                : CarbonImmutable::now(config('app.timezone'))->subDay();
        } catch (\Throwable) {
            $this->error('Nieprawidlowy format daty. Uzyj YYYY-MM-DD.');

            return self::FAILURE;
        }

        $recipient = (string) 'konrad@wspolnota.app';

        if ($recipient === '') {
            $this->error('Brak skonfigurowanego odbiorcy dobowego raportu operacyjnego.');

            return self::FAILURE;
        }

        $report = $builder->build($date);

        Mail::to($recipient)->queue(new SuperAdminDailyOperationsReportMessage($report));

        activity('superadmin-daily-reports')
            ->event('superadmin_daily_operations_report_sent')
            ->withProperties([
                'date' => $date->toDateString(),
                'recipient' => $recipient,
            ])
            ->log('Zakolejkowano dobowy raport operacyjny dla glownego superadministratora.');

        $this->info("Zakolejkowano dobowy raport operacyjny za {$date->toDateString()} na adres {$recipient}.");

        return self::SUCCESS;
    }
}
