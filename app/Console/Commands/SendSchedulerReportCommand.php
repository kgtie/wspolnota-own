<?php

namespace App\Console\Commands;

use App\Mail\SchedulerDailyReportMessage;
use App\Support\Scheduler\SchedulerDailyReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSchedulerReportCommand extends Command
{
    protected $signature = 'scheduler:send-report {--date= : Data raportu w formacie YYYY-MM-DD. Domyslnie dzisiaj.}';

    protected $description = 'Wysyla email z dziennym raportem wykonania zadan schedulera.';

    public function handle(SchedulerDailyReportBuilder $builder): int
    {
        $dateOption = $this->option('date');

        try {
            $date = $dateOption
                ? CarbonImmutable::createFromFormat('Y-m-d', (string) $dateOption, config('app.timezone'))
                : CarbonImmutable::now(config('app.timezone'));
        } catch (\Throwable) {
            $this->error('Nieprawidlowy format daty. Uzyj YYYY-MM-DD.');

            return self::FAILURE;
        }

        $recipient = (string) (
            config('services.wspolnota.scheduler_report_recipient')
            ?? config('scheduler.report_recipient_email')
            ?? 'konrad@wspolnota.app'
        );

        if ($recipient === '') {
            $this->error('Brak skonfigurowanego odbiorcy raportu schedulera.');

            return self::FAILURE;
        }

        $report = $builder->build($date);

        Mail::to($recipient)->queue(new SchedulerDailyReportMessage($report));

        activity('scheduler-reports')
            ->event('scheduler_daily_report_sent')
            ->withProperties([
                'date' => $date->toDateString(),
                'recipient' => $recipient,
            ])
            ->log('Zakolejkowano dzienny raport schedulera.');

        $this->info("Zakolejkowano raport schedulera za {$date->toDateString()} na adres {$recipient}.");

        return self::SUCCESS;
    }
}
