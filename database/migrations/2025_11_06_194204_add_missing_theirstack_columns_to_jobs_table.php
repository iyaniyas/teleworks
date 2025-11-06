<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom hanya jika belum ada (biar aman)
        if (!Schema::hasColumn('jobs', 'source')) {
            Schema::table('jobs', function (Blueprint $t) {
                $t->string('source')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('jobs', 'source_id')) {
            Schema::table('jobs', function (Blueprint $t) {
                $t->unsignedBigInteger('source_id')->nullable()->after('source');
                $t->index(['source', 'source_id'], 'jobs_source_sourceid_idx');
            });
        }

        // Kolom lain yang dipakai importer (dibuat kalau belum ada)
        $maybeAdd = function(string $col, callable $cb) {
            if (!Schema::hasColumn('jobs', $col)) {
                Schema::table('jobs', function (Blueprint $t) use ($cb) { $cb($t); });
            }
        };

        $maybeAdd('title', fn($t) => $t->string('title')->nullable());
        $maybeAdd('company', fn($t) => $t->string('company')->nullable());
        $maybeAdd('location', fn($t) => $t->string('location')->nullable());
        $maybeAdd('is_remote', fn($t) => $t->boolean('is_remote')->default(false));
        $maybeAdd('description', fn($t) => $t->longText('description')->nullable());
        $maybeAdd('apply_url', fn($t) => $t->string('apply_url')->nullable());
        $maybeAdd('date_posted', fn($t) => $t->date('date_posted')->nullable());
        $maybeAdd('hiring_organization', fn($t) => $t->string('hiring_organization')->nullable());
        $maybeAdd('job_location', fn($t) => $t->string('job_location')->nullable());
        $maybeAdd('applicant_location_requirements', fn($t) => $t->json('applicant_location_requirements')->nullable());
        $maybeAdd('base_salary_min', fn($t) => $t->decimal('base_salary_min', 12, 2)->nullable());
        $maybeAdd('base_salary_max', fn($t) => $t->decimal('base_salary_max', 12, 2)->nullable());
        $maybeAdd('base_salary_currency', fn($t) => $t->string('base_salary_currency', 8)->nullable());
        $maybeAdd('base_salary_unit', fn($t) => $t->string('base_salary_unit', 16)->nullable());
        $maybeAdd('direct_apply', fn($t) => $t->boolean('direct_apply')->default(false));
        $maybeAdd('employment_type', fn($t) => $t->string('employment_type')->nullable());
        $maybeAdd('identifier_name', fn($t) => $t->string('identifier_name')->nullable());
        $maybeAdd('identifier_value', fn($t) => $t->string('identifier_value')->nullable());
        $maybeAdd('job_location_type', fn($t) => $t->string('job_location_type')->nullable());
        $maybeAdd('valid_through', fn($t) => $t->date('valid_through')->nullable());
        $maybeAdd('raw', fn($t) => $t->json('raw')->nullable());
    }

    public function down(): void
    {
        // Rollback minimal (tidak wajib drop semua agar aman)
        Schema::table('jobs', function (Blueprint $t) {
            if (Schema::hasColumn('jobs', 'source_id')) {
                $t->dropIndex('jobs_source_sourceid_idx');
                $t->dropColumn('source_id');
            }
            if (Schema::hasColumn('jobs', 'source')) $t->dropColumn('source');
        });
        // Kolom lain biarkan (supaya data tidak hilang saat rollback ringan)
    }
};

