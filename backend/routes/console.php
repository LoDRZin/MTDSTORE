<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ReconcileStockCounters;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reconcilia os contadores Redis com o banco diariamente às 03:00
Schedule::job(new ReconcileStockCounters)->dailyAt('03:00')
    ->name('reconcile-stock-counters')
    ->withoutOverlapping();
