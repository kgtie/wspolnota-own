<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('announcements:ai --limit=80')
    ->dailyAt('00:07')
    ->withoutOverlapping();

Schedule::command('notifications:dispatch-delayed-content --limit=150')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('masses:dispatch-pending-reminders --limit=300')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('masses:dispatch-morning-email-reminders --limit=500')
    ->dailyAt('05:00')
    ->withoutOverlapping();

Schedule::command('news:publish-scheduled --limit=150')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('push:prune-dead-tokens --invalid-hours=24')
    ->hourlyAt(17)
    ->withoutOverlapping();

Schedule::command('scheduler:send-report')
    ->dailyAt('23:59')
    ->withoutOverlapping();
