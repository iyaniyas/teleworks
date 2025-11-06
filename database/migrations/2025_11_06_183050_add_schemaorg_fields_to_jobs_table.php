<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'date_posted')) {
                $table->date('date_posted')->nullable()->index();
            }
            // NOTE: JANGAN tambah 'description' & 'title' karena biasanya sudah ada.

            if (!Schema::hasColumn('jobs', 'hiring_organization')) {
                $table->string('hiring_organization')->nullable()->index();
            }
            if (!Schema::hasColumn('jobs', 'job_location')) {
                $table->string('job_location')->nullable()->index();
            }
            if (!Schema::hasColumn('jobs', 'applicant_location_requirements')) {
                $table->json('applicant_location_requirements')->nullable();
            }

            if (!Schema::hasColumn('jobs', 'base_salary_min')) {
                $table->decimal('base_salary_min', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('jobs', 'base_salary_max')) {
                $table->decimal('base_salary_max', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('jobs', 'base_salary_currency')) {
                $table->string('base_salary_currency', 8)->nullable();
            }
            if (!Schema::hasColumn('jobs', 'base_salary_unit')) {
                $table->string('base_salary_unit', 16)->nullable(); // hourly, monthly, yearly
            }

            if (!Schema::hasColumn('jobs', 'direct_apply')) {
                $table->boolean('direct_apply')->default(false)->index();
            }
            if (!Schema::hasColumn('jobs', 'employment_type')) {
                $table->string('employment_type')->nullable()->index(); // FULL_TIME, PART_TIME, CONTRACTOR, etc
            }

            if (!Schema::hasColumn('jobs', 'identifier_name')) {
                $table->string('identifier_name')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'identifier_value')) {
                $table->string('identifier_value')->nullable()->index();
            }

            if (!Schema::hasColumn('jobs', 'job_location_type')) {
                $table->string('job_location_type')->nullable()->index(); // "TELECOMMUTE", "ON_SITE", "HYBRID"
            }
            if (!Schema::hasColumn('jobs', 'valid_through')) {
                $table->date('valid_through')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $drops = [
                'date_posted',
                'hiring_organization',
                'job_location',
                'applicant_location_requirements',
                'base_salary_min',
                'base_salary_max',
                'base_salary_currency',
                'base_salary_unit',
                'direct_apply',
                'employment_type',
                'identifier_name',
                'identifier_value',
                'job_location_type',
                'valid_through',
            ];

            foreach ($drops as $col) {
                if (Schema::hasColumn('jobs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

