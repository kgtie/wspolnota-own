<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('announcements:ai')->dailyAt('00:00');
