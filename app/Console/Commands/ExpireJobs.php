<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;

class ExpireJobs extends Command
{
    protected $signature = 'jobs:expire';
    protected $description = 'Mark expired jobs as expired if past expires_at';

    public function handle(): void
    {
        $count = Job::where('status', 'published')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("✅ $count job(s) marked as expired.");
    }
}

