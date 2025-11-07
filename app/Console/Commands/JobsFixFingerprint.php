<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use Illuminate\Support\Str;
use DB;

class JobsFixFingerprint extends Command
{
    protected $signature = 'jobs:fix-fingerprint {--delete-duplicates : Actually delete duplicate rows}';
    protected $description = 'Generate fingerprint for jobs (if empty) and optionally delete duplicates by fingerprint.';

    public function handle()
    {
        $this->info('Start generating fingerprint for jobs...');
        $bar = $this->output->createProgressBar(Job::count());
        $bar->start();

        Job::chunkById(200, function($rows) use ($bar) {
            foreach ($rows as $job) {
                $fp = hash('sha256',
                    $this->normalize($job->title).'|'.
                    $this->normalize($job->company).'|'.
                    $this->normalize($job->location).'|'.
                    $this->normalize((string)($job->posted_at ?? ''))
                );

                if ($job->fingerprint !== $fp) {
                    $job->fingerprint = $fp;
                    $job->saveQuietly();
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info("\nFingerprint generation complete.");

        if ($this->option('delete-duplicates')) {
            $this->info('Finding duplicates by fingerprint...');
            $dups = DB::select("
                SELECT fingerprint, COUNT(*) as cnt, MIN(id) as keep_id
                FROM jobs
                WHERE fingerprint IS NOT NULL AND fingerprint <> ''
                GROUP BY fingerprint
                HAVING COUNT(*) > 1
            ");

            $this->info('Found '.count($dups).' duplicate fingerprint groups.');

            foreach ($dups as $d) {
                $this->info("Keeping id {$d->keep_id} for fingerprint {$d->fingerprint}, deleting others...");
                DB::delete("DELETE FROM jobs WHERE fingerprint = ? AND id <> ?", [$d->fingerprint, $d->keep_id]);
            }
            $this->info('Duplicate deletion finished.');
        } else {
            $this->info('To delete duplicates, re-run with --delete-duplicates flag AFTER verifying results.');
        }

        $this->info('DONE.');
        return 0;
    }

    private function normalize($s) {
        if ($s === null) return '';
        $s = (string) $s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/[^a-z0-9 ]/i', '', $s);
        return trim(Str::lower($s));
    }
}

