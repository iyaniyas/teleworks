<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// Tambahkan jika command tidak autodiscover atau untuk kepastian
use App\Console\Commands\ScrapeJobStreetSource;
use App\Console\Commands\ImportAdzunaJobs;
use App\Console\Commands\ImportTheirStackJobs;
use App\Console\Commands\ImportCareerjetJobs;

class Kernel extends ConsoleKernel
{
    /**
     * Jika butuh register manual, cantumkan di sini.
     * (Laravel modern biasanya autodiscover command dalam app/Console/Commands)
     */
    protected $commands = [
        ScrapeJobStreetSource::class,
        ImportAdzunaJobs::class,
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

        // Adzuna import tiap 6 jam pada menit ke-5
        $schedule->command('adzuna:import', [
                '--country' => 'us',
                '--what'    => 'remote OR work from home',
                '--age'     => 7,
                '--pages'   => 5,
            ])
            ->cron('5 */6 * * *')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // TheirStack import tiap jam
        $schedule->command('theirstack:import', [
                '--page'  => 1,
                '--limit' => 1,
            ])
            ->daily()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
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

