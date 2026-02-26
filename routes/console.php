<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('announcements:ai --limit=80')
    ->dailyAt('00:07')
    ->withoutOverlapping();

Schedule::command('announcements:notify-current --limit=150')
    ->dailyAt('00:12')
    ->withoutOverlapping();

Schedule::command('news:publish-scheduled --limit=150')
    ->everyFiveMinutes()
    ->withoutOverlapping();
