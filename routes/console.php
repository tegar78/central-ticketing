<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Backup Database Otomatis Harian (Setiap jam 02:00 Malam)
Illuminate\Support\Facades\Schedule::command('db:backup --gzip --retention=14')
    ->dailyAt('02:00')
    ->runInBackground();

