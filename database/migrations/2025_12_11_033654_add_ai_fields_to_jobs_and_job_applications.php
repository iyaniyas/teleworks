<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom AI di jobs
        Schema::table('jobs', function (Blueprint $table) {
            // berapa kali AI dipakai untuk deskripsi (buat nanti kalau mau limit)
            $table->unsignedTinyInteger('ai_generate_count')
                ->default(0)
                ->after('description_html');

            // ringkasan pelamar (optional, dipakai nanti kalau mau fitur summary)
            $table->longText('ai_applicants_summary')
                ->nullable()
                ->after('ai_generate_count');

            $table->unsignedTinyInteger('ai_applicants_summary_count')
                ->default(0)
                ->after('ai_applicants_summary');

            $table->timestamp('ai_applicants_summary_last_at')
                ->nullable()
                ->after('ai_applicants_summary_count');
        });

        // Tambah kolom AI di job_applications
        Schema::table('job_applications', function (Blueprint $table) {
            $table->decimal('ai_score', 5, 2)
                ->nullable()
                ->after('status');

            $table->text('ai_notes')
                ->nullable()
                ->after('ai_score');

            $table->timestamp('ai_scored_at')
                ->nullable()
                ->after('ai_notes');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'ai_generate_count',
                'ai_applicants_summary',
                'ai_applicants_summary_count',
                'ai_applicants_summary_last_at',
            ]);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'ai_score',
                'ai_notes',
                'ai_scored_at',
            ]);
        });
    }
};

