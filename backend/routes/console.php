<?php

use App\Jobs\GenerateWeeklySummaryJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new GenerateWeeklySummaryJob)->weeklyOn(1, '08:00');
