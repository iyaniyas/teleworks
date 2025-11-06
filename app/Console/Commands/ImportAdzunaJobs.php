<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\AdzunaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportAdzunaJobs extends Command
{
    /**
     * Nama perintah Artisan
     */
    protected $signature = 'adzuna:import
        {--country=us : Kode negara (misalnya: us, gb, sg)}
        {--what= : Kata kunci pencarian (default: remote)}
        {--where= : Lokasi pencarian (opsional)}
        {--age=0 : Umur maksimal posting (hari, 0 = tanpa batas)}
        {--pages=3 : Jumlah halaman yang diambil (per halaman max 50 hasil)}
        {--sleep=700 : Jeda antar halaman dalam milidetik}
        {--dry-run : Hanya simulasi tanpa menyimpan ke database}';

    protected $description = 'Import lowongan kerja dari Adzuna API ke tabel jobs (otomatis kata "remote")';

    /**
     * Jalankan command import
     */
    public function handle(AdzunaClient $client): int
    {
        $country = strtolower((string) $this->option('country') ?: 'us');
        $what    = trim((string) $this->option('what') ?: 'remote'); // otomatis remote
        $where   = trim((string) $this->option('where') ?: '');
        $age     = (int) $this->option('age');
        $pages   = (int) $this->option('pages');
        $sleep   = (int) $this->option('sleep');
        $dryRun  = (bool) $this->option('dry-run');

        $totalInserted = 0;
        $totalSkipped  = 0;
        $totalResults  = 0;

        $this->info("🔍 Mengambil lowongan dari Adzuna (country={$country}, what='{$what}')");

        for ($page = 1; $page <= $pages; $page++) {
            // Ambil data dari API
            $data = $client->fetchPage($country, $page, [
                'what'        => $what,
                'where'       => $where,
                'age'         => $age,
                'results_per_page' => 50,
            ]);

            $results = $data['results'] ?? [];
            $count = count($results);
            $totalResults += $count;

            if ($count === 0) {
                $this->warn("Halaman $page kosong, berhenti.");
                break;
            }

            foreach ($results as $ad) {
                $title    = $ad['title'] ?? null;
                $company  = $ad['company']['display_name'] ?? null;
                $location = $ad['location']['display_name'] ?? null;
                $redirect = $ad['redirect_url'] ?? null;
                $created  = $ad['created'] ?? null;
                $desc     = Str::limit(strip_tags($ad['description'] ?? ''), 5000);
                $sMin     = $ad['salary_min'] ?? null;
                $sMax     = $ad['salary_max'] ?? null;

                // Coba ambil FULL description via detail endpoint jika adref tersedia
                if (!empty($ad['adref']) && method_exists($client, 'fetchDetail')) {
                    try {
                        $detail = $client->fetchDetail($country, $ad['adref']);
                        if (is_array($detail) && !empty($detail['description'])) {
                            // pakai deskripsi lengkap (strip_tags untuk keamanan dasar)
                            $desc = Str::limit(strip_tags($detail['description']), 50000);
                        }
                    } catch (\Throwable $e) {
                        // diamkan saja; tetap pakai deskripsi ringkas
                    }
                }

                // Data wajib
                if (!$redirect || !$title) {
                    $totalSkipped++;
                    continue;
                }

                // Filter hanya WFH/remote jobs
                if (!Str::contains(Str::lower($title.' '.$desc), ['remote', 'work from home', 'wfh'])) {
                    $totalSkipped++;
                    continue;
                }

                // Cek duplikat
                $exists = Job::where('source', 'adzuna')
                    ->where('source_url', $redirect)
                    ->exists();
                if ($exists) {
                    $totalSkipped++;
                    continue;
                }

                // Dry-run = hanya simulasi
                if ($dryRun) {
                    $this->line("✅ [Dry-run] {$title} ({$company})");
                    $totalInserted++;
                    continue;
                }

                // SKIP jika sudah ada baris dengan source + source_url yang sama
                $dup = Job::where('source', 'adzuna')
                          ->where('source_url', $redirect)
                          ->exists();
                if ($dup) {
                    $totalSkipped++;
                    continue;
                }

                // Simpan ke database
                Job::create([
                    'title'       => $title,
                    'company'     => $company,
                    'location'    => $location,
                    'description' => $desc,

                    // kolom yang memang ada di tabel kamu:
                    'source'      => 'adzuna',
                    'source_url'  => $redirect,
                    'is_wfh'      => \Illuminate\Support\Str::contains(
                                        \Illuminate\Support\Str::lower(($title ?? '').' '.($desc ?? '')),
                                        ['remote','work from home','wfh']
                                    ) ? 1 : 0,
                    'expires_at'  => now()->addDays(45),

                    // opsional penanda internal (kolommu ada):
                    'is_imported' => 1,
                    // 'import_hash' => md5($redirect), // boleh pakai kalau mau
                ]);

                $totalInserted++;
            }

            $this->info("📄 Halaman $page: total=$count, inserted=$totalInserted, skipped=$totalSkipped");
            usleep($sleep * 1000);
        }

        $this->newLine();
        $this->info("✅ Import selesai.");
        $this->line("Hasil: total_results={$totalResults}, inserted={$totalInserted}, skipped={$totalSkipped}, dry_run=" . ($dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}

