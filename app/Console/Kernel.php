<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// Tambahkan jika command tidak autodiscover atau untuk kepastian
use App\Console\Commands\ScrapeJobStreetSource;
use App\Console\Commands\ImportJsaJobs;
use App\Console\Commands\ImportTheirStackJobs;
use App\Console\Commands\ImportCareerjetJobs;
use \App\Console\Commands\ImportRemoteOk;

class Kernel extends ConsoleKernel
{
    /**
     * Jika butuh register manual, cantumkan di sini.
     * (Laravel modern biasanya autodiscover command dalam app/Console/Commands)
     */
    protected $commands = [
	ScrapeJobStreetSource::class,
	ImportRemoteOk::class,
        ImportJsaJobs::class,
	ImportTheirStackJobs::class,
	ImportCareerjetJobs::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Expire job tiap jam
        $schedule->command('jobs:expire')
            ->hourly();
	// JSA — jalan setiap hari jam 01:00 WIB
    $schedule->command('jsa:import --q="" --location="indonesia" --results=1 --limit=5')
        ->dailyAt('01:00')
        ->withoutOverlapping();

    // TheirStack — jalan setiap hari jam 01:10 WIB
    $schedule->command('theirstack:import --q= --pages=1 --per-page=5 --avoid=job_id_not')
        ->dailyAt('01:10')
	->withoutOverlapping();

    //remote ok importer
    $schedule->command('import:remoteok')->everyThirtyMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}

