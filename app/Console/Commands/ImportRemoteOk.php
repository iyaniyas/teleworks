<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RemoteOkImporter;

class ImportRemoteOk extends Command
{
    protected $signature = 'import:remoteok {--source=} {--limit=} {--no-output}';

    protected $description = 'Import jobs from RemoteOK JSON feed into jobs table';

    protected $importer;

    public function __construct(RemoteOkImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    public function handle()
    {
        $source = $this->option('source') ?: null;
        $limitOpt = $this->option('limit');
        $limit = null;

        if (!is_null($limitOpt) && $limitOpt !== '') {
            $limit = intval($limitOpt);
            if ($limit <= 0) {
                $this->error('Limit must be a positive integer.');
                return 1;
            }
        }

        if (! $this->option('no-output')) {
            $this->info('Starting RemoteOK import...');
            if ($limit) {
                $this->info('Limit: ' . $limit);
            }
        }

        $result = $this->importer->import($source, $limit);

        if (! $this->option('no-output')) {

            if (isset($result['status']) && $result['status'] === 'error') {
                $this->error('Import failed: ' . ($result['message'] ?? 'unknown'));
                return 1;
            }

            $this->info("Processed: {$result['processed']}");
            $this->info("Imported : {$result['imported']}");
            $this->info("Updated  : {$result['updated']}");
            $this->info("Skipped  : {$result['skipped']}");
            $this->info("Errors   : {$result['errors']}");
        }

        return 0;
    }
}

