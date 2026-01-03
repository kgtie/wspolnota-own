<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Generowanie streszczeń AI dla ogłoszeń
 * Uruchamiane codziennie o północy
 */
Schedule::command('announcements:generate-ai-summaries --limit=100')
    ->dailyAt('00:00');
