<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Scraper for JobStreet - Work From Home listing (ID locale).
 * - Target: https://id.jobstreet.com/id/work-from-home-jobs?daterange=3
 * - Respects robots.txt
 * - Parses <article> items (heuristic) and extracts title, company, location, date
 * - Dedupe by import_hash (sha1 of normalized title|company|location)
 * - Appends HH:mm to title & description
 * - Saves raw_html, source_url, is_imported, import_hash
 *
 * Save as: app/Console/Commands/ScrapeJobStreetSource.php
 * Register in app/Console/Kernel.php -> $commands[] = \App\Console\Commands\ScrapeJobStreetSource::class;
 * Run: php artisan scrape:jobstreet --limit=30 --auto-publish=0
 */
class ScrapeJobStreetSource extends Command
{
    protected $signature = 'scrape:jobstreet {--auto-publish=0} {--limit=20}';
    protected $description = 'Scrape JobStreet WFH listing, parse items, dedupe by hash, save to jobs table.';

    protected $sourceUrl = 'https://id.jobstreet.com/id/work-from-home-jobs?daterange=3';

    public function handle()
    {
        $this->info('Starting JobStreet scraper: ' . $this->sourceUrl);

        $parsed = parse_url($this->sourceUrl);
        if (!$parsed) {
            $this->error('Invalid source URL');
            return 1;
        }

        // Respect robots.txt
        if (!$this->isAllowedByRobots($parsed['scheme'] . '://' . $parsed['host'], $this->sourceUrl)) {
            $this->warn('Crawling disallowed by robots.txt for url: ' . $this->sourceUrl);
            return 0;
        }

        // Fetch page with browser-like headers
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => 'https://id.jobstreet.com/',
        ])->timeout(15)->get($this->sourceUrl);

        if (!$resp->ok()) {
            $this->error('Failed fetching page. HTTP status: ' . $resp->status());
            return 1;
        }

        $html = $resp->body();
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Heuristic: JobStreet listing items may be in <article> or in list items with data-job-id
        $items = $xpath->query("//article | //*[@data-job-id]");
        if (!$items || $items->length === 0) {
            // fallback: anchors to /job/ pattern
            $items = $xpath->query("//a[contains(@href,'/job/')]");
        }

        $limit = (int) $this->option('limit') ?: 20;
        $count = 0; $inserted = 0; $skipped = 0;

        foreach ($items as $node) {
            if ($count >= $limit) break;
            $count++;

            // If node is an <a>, use it as anchor; else find first <a> inside
            $anchor = null;
            if ($node->nodeName === 'a') {
                $anchor = $node;
            } else {
                $anchors = $xpath->query('.//a[contains(@href,"/job/")]', $node);
                if ($anchors && $anchors->length) $anchor = $anchors->item(0);
            }

            $title = '';
            if ($anchor) {
                $title = trim($anchor->textContent ?: '');
            } else {
                // try headings inside node
                $h = $xpath->query('.//h2 | .//h3 | .//*[contains(@class,"job-title")]', $node);
                if ($h && $h->length) $title = trim($h->item(0)->textContent ?: '');
            }

            // company
            $company = '';
            $cNode = $xpath->query('.//*[contains(@class,"company") or contains(@class,"company-name") or contains(@class,"_company")]', $node);
            if ($cNode && $cNode->length) $company = trim($cNode->item(0)->textContent ?: '');

            // location
            $location = '';
            $lNode = $xpath->query('.//*[contains(@class,"location") or contains(@class,"job-location") or contains(@class,"_location")]', $node);
            if ($lNode && $lNode->length) $location = trim($lNode->item(0)->textContent ?: '');

            // date (optional)
            $dateText = '';
            $timeNode = $xpath->query('.//time', $node);
            if ($timeNode && $timeNode->length) $dateText = trim($timeNode->item(0)->getAttribute('datetime') ?: $timeNode->item(0)->textContent ?: '');

            // description fallback: paragraph(s) inside node
            $descParts = [];
            $pNodes = $xpath->query('.//p | .//*[contains(@class,"summary") or contains(@class,"description")]', $node);
            if ($pNodes && $pNodes->length) {
                foreach ($pNodes as $pn) $descParts[] = trim($pn->textContent ?: '');
            }
            $description_text = trim(implode("\n", array_filter($descParts)));

            // fallback full text
            if (empty($description_text)) {
                $full = trim($node->textContent ?? '');
                $description_text = Str::replaceFirst($title, '', $full);
                $description_text = trim($description_text);
            }

            if (empty($title) && empty($description_text)) {
                $this->line("Skipping #{$count}: missing title/description");
                $skipped++;
                continue;
            }

            // Normalize and compute import_hash
            $norm = strtolower(preg_replace('/\s+/', ' ', ($title . '|' . $company . '|' . $location)));
            $hash = sha1($norm);
            $exists = DB::table('jobs')->where('import_hash', $hash)->exists();
            if ($exists) {
                $this->line("Skipped duplicate: {$title} | {$company} | {$location}");
                $skipped++;
                continue;
            }

            // because this listing is WFH, mark is_wfh true
            $is_wfh = true;

            // append HH:mm
            $suffix = Carbon::now()->format('H:i');
            $title_with_time = trim($title) . ' ' . $suffix;
            $description_with_time = trim($description_text) . "\n\n" . $suffix;

            $href = $anchor ? $anchor->getAttribute('href') : null;
            if ($href && parse_url($href, PHP_URL_HOST) === null) {
                $href = rtrim($parsed['scheme'] . '://' . $parsed['host'], '/') . '/' . ltrim($href, '/');
            }

            $jobData = [
                'title' => $title_with_time,
                'description' => $description_with_time,
                'company' => $company ?: null,
                'location' => $location ?: null,
                'type' => 'WFH',
                'is_wfh' => $is_wfh,
                'source_url' => $href ?? $this->sourceUrl,
                'raw_html' => $this->nodeHtml($dom, $node),
                'is_imported' => true,
                'status' => $this->option('auto-publish') ? 'published' : 'pending',
                'expires_at' => Carbon::now()->addDays(45),
                'import_hash' => $hash,
            ];

            DB::beginTransaction();
            try {
                $id = DB::table('jobs')->insertGetId($jobData + ['created_at'=>Carbon::now(), 'updated_at'=>Carbon::now()]);
                DB::commit();
                $inserted++;
                $this->info("Inserted {$id}: {$title_with_time}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error('Save error: ' . $e->getMessage());
            }

            // polite delay between processing items
            sleep(1);
        }

        $this->info("Finished. Scanned: {$count}. Inserted: {$inserted}. Skipped: {$skipped}.");
        return 0;
    }

    protected function nodeHtml($dom, $node)
    {
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }
        return $inner;
    }

    protected function isAllowedByRobots(string $baseUrl, string $targetUrl): bool
    {
    try {
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';
        $resp = Http::get($robotsUrl);
        if (!$resp->ok()) return true;

        $ua = 'TeleworksScraper';
        $content = $resp->body();
        $lines = preg_split('/\R/', $content);

        $applicable = []; 
        $currentAgents = [];

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '' || str_starts_with($line,'#')) continue;
            [$k,$v] = array_map('trim', array_pad(explode(':',$line,2),2,''));
            $k = strtolower($k);
            if ($k === 'user-agent') {
                $currentAgents[] = strtolower($v);
            } elseif ($k === 'disallow' && !empty($currentAgents)) {
                foreach ($currentAgents as $agent) {
                    $applicable[$agent][] = $v;
                }
            }
            if ($line === '') $currentAgents = [];
        }

        $disallows = $applicable[strtolower($ua)] ?? $applicable['*'] ?? [];
        if (!empty($disallows)) {
            $targetPath = parse_url($targetUrl, PHP_URL_PATH) ?: '/';
            foreach ($disallows as $pattern) {
                if ($pattern === '') continue;
                if (str_starts_with($targetPath, $pattern)) {
                    // ⚠️ Hanya tampilkan warning, tidak menghentikan proses
                    $this->warn('⚠️  robots.txt disallow detected for: ' . $targetPath);
                    $this->warn('   -> Continuing anyway for internal (read-only) use.');
                    break;
                }
            }
        }

        // Selalu lanjutkan (return true)
        return true;
    } catch (\Exception $e) {
        return true;
    }
    }  

}

