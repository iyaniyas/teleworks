<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTheirstackFieldsToJobsTable extends Migration
{
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            // only add columns if they don't exist to be safe on existing DB
            if (!Schema::hasColumn('jobs', 'identifier_name')) {
                $table->string('identifier_name')->nullable()->after('employment_type_raw');
            }

            if (!Schema::hasColumn('jobs', 'identifier_value')) {
                $table->string('identifier_value')->nullable()->index()->after('identifier_name');
            }

            if (!Schema::hasColumn('jobs', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('direct_apply');
            }

            if (!Schema::hasColumn('jobs', 'employment_type_raw')) {
                $table->longText('employment_type_raw')->nullable()->after('employment_type');
            }

            if (!Schema::hasColumn('jobs', 'applicant_location_requirements')) {
                $table->longText('applicant_location_requirements')->nullable()->after('job_location');
            }

            if (!Schema::hasColumn('jobs', 'base_salary_min')) {
                $table->decimal('base_salary_min', 12, 2)->nullable()->after('applicant_location_requirements');
            }

            if (!Schema::hasColumn('jobs', 'base_salary_max')) {
                $table->decimal('base_salary_max', 12, 2)->nullable()->after('base_salary_min');
            }

            if (!Schema::hasColumn('jobs', 'base_salary_currency')) {
                $table->string('base_salary_currency', 8)->nullable()->after('base_salary_max');
            }

            if (!Schema::hasColumn('jobs', 'base_salary_unit')) {
                $table->string('base_salary_unit', 16)->nullable()->after('base_salary_currency');
            }

            if (!Schema::hasColumn('jobs', 'base_salary_string')) {
                $table->string('base_salary_string', 255)->nullable()->after('base_salary_unit');
            }

            if (!Schema::hasColumn('jobs', 'direct_apply')) {
                $table->tinyInteger('direct_apply')->default(0)->after('base_salary_string')->index();
            }

            if (!Schema::hasColumn('jobs', 'job_location_type')) {
                $table->string('job_location_type')->nullable()->after('identifier_value');
            }

            if (!Schema::hasColumn('jobs', 'valid_through')) {
                $table->date('valid_through')->nullable()->after('job_location_type')->index();
            }

            if (!Schema::hasColumn('jobs', 'is_remote')) {
                $table->tinyInteger('is_remote')->default(0)->after('valid_through')->index();
            }

            if (!Schema::hasColumn('jobs', 'apply_url')) {
                $table->string('apply_url')->nullable()->after('is_remote');
            }

            if (!Schema::hasColumn('jobs', 'easy_apply')) {
                $table->tinyInteger('easy_apply')->default(0)->after('apply_url');
            }

            if (!Schema::hasColumn('jobs', 'raw')) {
                $table->longText('raw')->nullable()->after('easy_apply');
            }
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'identifier_name')) {
                $table->dropColumn('identifier_name');
            }
            if (Schema::hasColumn('jobs', 'identifier_value')) {
                $table->dropIndex(['identifier_value']);
                $table->dropColumn('identifier_value');
            }
            if (Schema::hasColumn('jobs', 'employment_type')) {
                $table->dropColumn('employment_type');
            }
            if (Schema::hasColumn('jobs', 'employment_type_raw')) {
                $table->dropColumn('employment_type_raw');
            }
            if (Schema::hasColumn('jobs', 'applicant_location_requirements')) {
                $table->dropColumn('applicant_location_requirements');
            }
            if (Schema::hasColumn('jobs', 'base_salary_min')) {
                $table->dropColumn('base_salary_min');
            }
            if (Schema::hasColumn('jobs', 'base_salary_max')) {
                $table->dropColumn('base_salary_max');
            }
            if (Schema::hasColumn('jobs', 'base_salary_currency')) {
                $table->dropColumn('base_salary_currency');
            }
            if (Schema::hasColumn('jobs', 'base_salary_unit')) {
                $table->dropColumn('base_salary_unit');
            }
            if (Schema::hasColumn('jobs', 'base_salary_string')) {
                $table->dropColumn('base_salary_string');
            }
            if (Schema::hasColumn('jobs', 'direct_apply')) {
                $table->dropColumn('direct_apply');
            }
            if (Schema::hasColumn('jobs', 'job_location_type')) {
                $table->dropColumn('job_location_type');
            }
            if (Schema::hasColumn('jobs', 'valid_through')) {
                $table->dropColumn('valid_through');
            }
            if (Schema::hasColumn('jobs', 'is_remote')) {
                $table->dropColumn('is_remote');
            }
            if (Schema::hasColumn('jobs', 'apply_url')) {
                $table->dropColumn('apply_url');
            }
            if (Schema::hasColumn('jobs', 'easy_apply')) {
                $table->dropColumn('easy_apply');
            }
            if (Schema::hasColumn('jobs', 'raw')) {
                $table->dropColumn('raw');
            }
        });
    }
}

