<?php

use App\Console\Commands\SyncLockerCommand;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$syncOn = true;
$minutes = 5;

try {
    if (Schema::hasTable('settings')) {
        $syncOn = (string) Setting::get('sync_automatica', '1') !== '0';
        $minutes = max(1, min(1440, (int) Setting::get('sync_minutos', 5)));
    }
} catch (\Throwable) {
    // Mantém defaults durante migrate / bootstrap sem DB.
}

if ($syncOn) {
    Schedule::command(SyncLockerCommand::class)
        ->cron("*/{$minutes} * * * *")
        ->withoutOverlapping();
}
